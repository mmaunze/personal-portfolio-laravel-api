<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['roles', 'permissions']);

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('bio', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->filled('role')) {
                $query->role($request->role);
            }

            // Filter by status
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users,
                'meta' => [
                    'total_users' => User::count(),
                    'active_users' => User::active()->count(),
                    'admin_users' => User::role('admin')->count(),
                    'editor_users' => User::role('editor')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar utilizadores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'location' => 'nullable|string|max:255',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'boolean',
            'avatar' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'bio' => $request->bio,
                'phone' => $request->phone,
                'website' => $request->website,
                'location' => $request->location,
                'is_active' => $request->get('is_active', true),
                'email_verified_at' => now()
            ];

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $userData['avatar'] = $avatarPath;
            }

            $user = User::create($userData);

            // Assign role
            $user->assignRole($request->role);

            return response()->json([
                'success' => true,
                'message' => 'Utilizador criado com sucesso',
                'data' => $user->load('roles', 'permissions')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar utilizador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        try {
            $user->load(['roles', 'permissions']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $user->stats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter utilizador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'location' => 'nullable|string|max:255',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'boolean',
            'avatar' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'bio' => $request->bio,
                'phone' => $request->phone,
                'website' => $request->website,
                'location' => $request->location,
                'is_active' => $request->get('is_active', $user->is_active)
            ];

            // Update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Delete old avatar
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $updateData['avatar'] = $avatarPath;
            }

            $user->update($updateData);

            // Update role
            $user->syncRoles([$request->role]);

            return response()->json([
                'success' => true,
                'message' => 'Utilizador actualizado com sucesso',
                'data' => $user->fresh()->load('roles', 'permissions')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao actualizar utilizador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        try {
            // Prevent deleting the current user
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não pode eliminar a sua própria conta'
                ], 403);
            }

            // Prevent deleting the last admin
            if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não pode eliminar o último administrador'
                ], 403);
            }

            // Delete avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Delete user tokens
            $user->tokens()->delete();

            // Delete user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilizador eliminado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao eliminar utilizador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available roles
     */
    public function getRoles()
    {
        try {
            $roles = Role::all(['id', 'name']);
            
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        try {
            // Prevent deactivating the current user
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não pode desactivar a sua própria conta'
                ], 403);
            }

            // Prevent deactivating the last admin
            if ($user->hasRole('admin') && $user->is_active && User::role('admin')->active()->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não pode desactivar o último administrador activo'
                ], 403);
            }

            $user->update(['is_active' => !$user->is_active]);

            // Revoke tokens if deactivating
            if (!$user->is_active) {
                $user->tokens()->delete();
            }

            $status = $user->is_active ? 'activado' : 'desactivado';

            return response()->json([
                'success' => true,
                'message' => "Utilizador {$status} com sucesso",
                'data' => $user->fresh()->load('roles', 'permissions')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do utilizador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'admin_users' => User::role('admin')->count(),
                'editor_users' => User::role('editor')->count(),
                'author_users' => User::role('author')->count(),
                'viewer_users' => User::role('viewer')->count(),
                'recent_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'users_with_posts' => User::has('posts')->count(),
                'users_with_projects' => User::has('projects')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

