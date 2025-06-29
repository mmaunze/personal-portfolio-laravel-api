<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    /**
     * Display a listing of downloads
     */
    public function index(Request $request)
    {
        try {
            $query = Download::query();

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->byCategory($request->category);
            }

            // Filter by file type
            if ($request->filled('file_type')) {
                $query->byFileType($request->file_type);
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

            // Filter by featured
            if ($request->filled('featured')) {
                $query->featured($request->featured === 'true');
            }

            // Filter by registration requirement
            if ($request->filled('requires_registration')) {
                $query->requiresRegistration($request->requires_registration === 'true');
            }

            // Filter by tags
            if ($request->filled('tags')) {
                $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
                foreach ($tags as $tag) {
                    $query->whereJsonContains('tags', trim($tag));
                }
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $downloads = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $downloads,
                'meta' => [
                    'total_downloads' => Download::count(),
                    'published_downloads' => Download::published()->count(),
                    'draft_downloads' => Download::where('is_published', false)->count(),
                    'featured_downloads' => Download::featured()->count(),
                    'categories' => Download::distinct()->pluck('category')->filter()->values(),
                    'file_types' => Download::distinct()->pluck('file_type')->filter()->values(),
                    'authors' => Download::distinct()->pluck('author')->filter()->values()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar downloads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created download
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:downloads',
            'slug' => 'nullable|string|max:255|unique:downloads',
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|max:51200', // 50MB max
            'category' => 'required|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'author' => 'required|string|max:255',
            'version' => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'requires_registration' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            
            // Store file
            $filePath = $file->storeAs('downloads', $fileName, 'public');
            $fileUrl = asset('storage/' . $filePath);

            $downloadData = [
                'title' => $request->title,
                'slug' => $request->slug ?: Str::slug($request->title),
                'description' => $request->description,
                'file_name' => $originalName,
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'category' => $request->category,
                'tags' => $request->tags ?: [],
                'author' => $request->author,
                'version' => $request->version,
                'is_featured' => $request->get('is_featured', false),
                'is_published' => $request->get('is_published', false),
                'requires_registration' => $request->get('requires_registration', false),
                'download_count' => 0
            ];

            $download = Download::create($downloadData);

            return response()->json([
                'success' => true,
                'message' => 'Download criado com sucesso',
                'data' => $download
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar download',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified download
     */
    public function show($id)
    {
        try {
            $download = Download::where('id', $id)->orWhere('slug', $id)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $download
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download não encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified download
     */
    public function update(Request $request, Download $download)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:downloads,title,' . $download->id,
            'slug' => 'nullable|string|max:255|unique:downloads,slug,' . $download->id,
            'description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|max:51200', // 50MB max
            'category' => 'required|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'author' => 'required|string|max:255',
            'version' => 'nullable|string|max:50',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'requires_registration' => 'boolean'
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
                'description' => $request->description,
                'category' => $request->category,
                'tags' => $request->tags ?: [],
                'author' => $request->author,
                'version' => $request->version,
                'is_featured' => $request->get('is_featured', $download->is_featured),
                'is_published' => $request->get('is_published', $download->is_published),
                'requires_registration' => $request->get('requires_registration', $download->requires_registration)
            ];

            // Handle file replacement
            if ($request->hasFile('file')) {
                // Delete old file
                Storage::disk('public')->delete($download->file_path);
                
                $file = $request->file('file');
                $originalName = $file->getClientOriginalName();
                $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                
                // Store new file
                $filePath = $file->storeAs('downloads', $fileName, 'public');
                $fileUrl = asset('storage/' . $filePath);

                $updateData = array_merge($updateData, [
                    'file_name' => $originalName,
                    'file_path' => $filePath,
                    'file_url' => $fileUrl,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'mime_type' => $file->getMimeType()
                ]);
            }

            $download->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Download actualizado com sucesso',
                'data' => $download->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao actualizar download',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified download
     */
    public function destroy(Download $download)
    {
        try {
            // Delete associated file
            Storage::disk('public')->delete($download->file_path);

            $download->delete();

            return response()->json([
                'success' => true,
                'message' => 'Download eliminado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao eliminar download',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file
     */
    public function downloadFile($id, Request $request)
    {
        try {
            $download = Download::where('id', $id)->orWhere('slug', $id)->firstOrFail();

            // Check if download is published
            if (!$download->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Download não disponível'
                ], 403);
            }

            // Check registration requirement
            if ($download->requires_registration && !auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registo necessário para fazer download'
                ], 401);
            }

            // Check if file exists
            if (!$download->fileExists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ficheiro não encontrado'
                ], 404);
            }

            // Increment download count
            $download->incrementDownloads();

            // Return file download
            return Storage::disk('public')->download($download->file_path, $download->file_name);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer download',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle download publication status
     */
    public function togglePublished(Download $download)
    {
        try {
            $download->update(['is_published' => !$download->is_published]);
            
            $status = $download->is_published ? 'publicado' : 'despublicado';

            return response()->json([
                'success' => true,
                'message' => "Download {$status} com sucesso",
                'data' => $download->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do download',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Download $download)
    {
        try {
            $download->update(['is_featured' => !$download->is_featured]);
            
            $status = $download->is_featured ? 'destacado' : 'não destacado';

            return response()->json([
                'success' => true,
                'message' => "Download {$status} com sucesso",
                'data' => $download->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status de destaque',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get download statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_downloads' => Download::count(),
                'published_downloads' => Download::published()->count(),
                'draft_downloads' => Download::where('is_published', false)->count(),
                'featured_downloads' => Download::featured()->count(),
                'total_download_count' => Download::sum('download_count'),
                'total_file_size' => Download::sum('file_size'),
                'recent_downloads' => Download::where('created_at', '>=', now()->subDays(30))->count(),
                'popular_downloads' => Download::orderBy('download_count', 'desc')->limit(5)->get(['id', 'title', 'download_count']),
                'categories_count' => Download::distinct()->whereNotNull('category')->count('category'),
                'file_types_count' => Download::distinct()->count('file_type'),
                'authors_count' => Download::distinct()->count('author')
            ];

            // Format file size
            $totalSize = $stats['total_file_size'];
            if ($totalSize >= 1073741824) {
                $stats['formatted_file_size'] = number_format($totalSize / 1073741824, 2) . ' GB';
            } elseif ($totalSize >= 1048576) {
                $stats['formatted_file_size'] = number_format($totalSize / 1048576, 2) . ' MB';
            } elseif ($totalSize >= 1024) {
                $stats['formatted_file_size'] = number_format($totalSize / 1024, 2) . ' KB';
            } else {
                $stats['formatted_file_size'] = $totalSize . ' bytes';
            }

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
            $categories = Download::distinct()
                ->whereNotNull('category')
                ->pluck('category')
                ->map(function ($category) {
                    return [
                        'name' => $category,
                        'count' => Download::where('category', $category)->count(),
                        'published_count' => Download::where('category', $category)->published()->count()
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
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:publish,unpublish,feature,unfeature,delete',
            'download_ids' => 'required|array|min:1',
            'download_ids.*' => 'integer|exists:downloads,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $downloads = Download::whereIn('id', $request->download_ids);
            $count = $downloads->count();

            switch ($request->action) {
                case 'publish':
                    $downloads->update(['is_published' => true]);
                    $message = "{$count} download(s) publicado(s) com sucesso";
                    break;
                    
                case 'unpublish':
                    $downloads->update(['is_published' => false]);
                    $message = "{$count} download(s) despublicado(s) com sucesso";
                    break;
                    
                case 'feature':
                    $downloads->update(['is_featured' => true]);
                    $message = "{$count} download(s) destacado(s) com sucesso";
                    break;
                    
                case 'unfeature':
                    $downloads->update(['is_featured' => false]);
                    $message = "{$count} download(s) removido(s) do destaque com sucesso";
                    break;
                    
                case 'delete':
                    // Delete associated files
                    $downloads->get()->each(function ($download) {
                        Storage::disk('public')->delete($download->file_path);
                    });
                    
                    $downloads->delete();
                    $message = "{$count} download(s) eliminado(s) com sucesso";
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

