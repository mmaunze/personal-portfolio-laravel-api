<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Display a listing of posts
     */
    public function index(Request $request)
    {
        try {
            $query = Post::query();

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->byCategory($request->category);
            }

            // Filter by author
            if ($request->filled('author')) {
                $query->byAuthor($request->author);
            }

            // Filter by status
            if ($request->filled('status')) {
                if ($request->status === 'published') {
                    $query->published();
                } elseif ($request->status === 'draft') {
                    $query->where('is_published', false);
                }
            }

            // Filter by tags
            if ($request->filled('tags')) {
                $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
                foreach ($tags as $tag) {
                    $query->whereJsonContains('tags', trim($tag));
                }
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('publish_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('publish_date', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $posts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $posts,
                'meta' => [
                    'total_posts' => Post::count(),
                    'published_posts' => Post::published()->count(),
                    'draft_posts' => Post::where('is_published', false)->count(),
                    'categories' => Post::distinct()->pluck('category')->filter()->values(),
                    'authors' => Post::distinct()->pluck('author')->filter()->values()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar posts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created post
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:posts',
            'slug' => 'nullable|string|max:255|unique:posts',
            'excerpt' => 'nullable|string|max:500',
            'full_content' => 'required|string',
            'author' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'image_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $postData = [
                'title' => $request->title,
                'slug' => $request->slug ?: Str::slug($request->title),
                'excerpt' => $request->excerpt,
                'full_content' => $request->full_content,
                'author' => $request->author,
                'publish_date' => $request->publish_date,
                'category' => $request->category,
                'tags' => $request->tags ?: [],
                'image_url' => $request->image_url,
                'is_published' => $request->get('is_published', false),
                'views_count' => 0
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('posts', 'public');
                $postData['image_url'] = asset('storage/' . $imagePath);
            }

            $post = Post::create($postData);

            return response()->json([
                'success' => true,
                'message' => 'Post criado com sucesso',
                'data' => $post
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified post
     */
    public function show($id)
    {
        try {
            $post = Post::where('id', $id)->orWhere('slug', $id)->firstOrFail();
            
            // Increment views for published posts
            if ($post->is_published) {
                $post->incrementViews();
            }

            return response()->json([
                'success' => true,
                'data' => $post
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Post não encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified post
     */
    public function update(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:posts,title,' . $post->id,
            'slug' => 'nullable|string|max:255|unique:posts,slug,' . $post->id,
            'excerpt' => 'nullable|string|max:500',
            'full_content' => 'required|string',
            'author' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'image_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
            'image' => 'nullable|image|max:2048'
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
                'title' => $request->title,
                'slug' => $request->slug ?: Str::slug($request->title),
                'excerpt' => $request->excerpt,
                'full_content' => $request->full_content,
                'author' => $request->author,
                'publish_date' => $request->publish_date,
                'category' => $request->category,
                'tags' => $request->tags ?: [],
                'is_published' => $request->get('is_published', $post->is_published)
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if it's stored locally
                if ($post->image_url && Str::contains($post->image_url, 'storage/posts/')) {
                    $oldImagePath = str_replace(asset('storage/'), '', $post->image_url);
                    Storage::disk('public')->delete($oldImagePath);
                }
                
                $imagePath = $request->file('image')->store('posts', 'public');
                $updateData['image_url'] = asset('storage/' . $imagePath);
            } elseif ($request->filled('image_url')) {
                $updateData['image_url'] = $request->image_url;
            }

            $post->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Post actualizado com sucesso',
                'data' => $post->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao actualizar post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified post
     */
    public function destroy(Post $post)
    {
        try {
            // Delete associated image if stored locally
            if ($post->image_url && Str::contains($post->image_url, 'storage/posts/')) {
                $imagePath = str_replace(asset('storage/'), '', $post->image_url);
                Storage::disk('public')->delete($imagePath);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post eliminado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao eliminar post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle post publication status
     */
    public function togglePublished(Post $post)
    {
        try {
            $post->update(['is_published' => !$post->is_published]);
            
            $status = $post->is_published ? 'publicado' : 'despublicado';

            return response()->json([
                'success' => true,
                'message' => "Post {$status} com sucesso",
                'data' => $post->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get post statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_posts' => Post::count(),
                'published_posts' => Post::published()->count(),
                'draft_posts' => Post::where('is_published', false)->count(),
                'total_views' => Post::sum('views_count'),
                'recent_posts' => Post::where('created_at', '>=', now()->subDays(30))->count(),
                'popular_posts' => Post::orderBy('views_count', 'desc')->limit(5)->get(['id', 'title', 'views_count']),
                'categories_count' => Post::distinct()->whereNotNull('category')->count('category'),
                'authors_count' => Post::distinct()->count('author'),
                'avg_reading_time' => round(Post::get()->avg('reading_time'), 1)
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

    /**
     * Get categories
     */
    public function getCategories()
    {
        try {
            $categories = Post::distinct()
                ->whereNotNull('category')
                ->pluck('category')
                ->map(function ($category) {
                    return [
                        'name' => $category,
                        'count' => Post::where('category', $category)->count(),
                        'published_count' => Post::where('category', $category)->published()->count()
                    ];
                })
                ->sortByDesc('count')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter categorias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tags
     */
    public function getTags()
    {
        try {
            $allTags = Post::whereNotNull('tags')->pluck('tags')->flatten()->unique();
            
            $tags = $allTags->map(function ($tag) {
                return [
                    'name' => $tag,
                    'count' => Post::whereJsonContains('tags', $tag)->count(),
                    'published_count' => Post::whereJsonContains('tags', $tag)->published()->count()
                ];
            })->sortByDesc('count')->values();

            return response()->json([
                'success' => true,
                'data' => $tags
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:publish,unpublish,delete',
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'integer|exists:posts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $posts = Post::whereIn('id', $request->post_ids);
            $count = $posts->count();

            switch ($request->action) {
                case 'publish':
                    $posts->update(['is_published' => true]);
                    $message = "{$count} post(s) publicado(s) com sucesso";
                    break;
                    
                case 'unpublish':
                    $posts->update(['is_published' => false]);
                    $message = "{$count} post(s) despublicado(s) com sucesso";
                    break;
                    
                case 'delete':
                    // Delete associated images
                    $posts->get()->each(function ($post) {
                        if ($post->image_url && Str::contains($post->image_url, 'storage/posts/')) {
                            $imagePath = str_replace(asset('storage/'), '', $post->image_url);
                            Storage::disk('public')->delete($imagePath);
                        }
                    });
                    
                    $posts->delete();
                    $message = "{$count} post(s) eliminado(s) com sucesso";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar operação em lote',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

