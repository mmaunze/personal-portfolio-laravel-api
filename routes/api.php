<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\ContactController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Public contact route
Route::post('contact', [ContactController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // User management routes (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('users-roles', [UserController::class, 'getRoles']);
        Route::get('users-stats', [UserController::class, 'getStats']);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    });

    // Post management routes
    Route::middleware('permission:view-posts')->group(function () {
        Route::get('posts', [PostController::class, 'index']);
        Route::get('posts/{post}', [PostController::class, 'show']);
        Route::get('posts-stats', [PostController::class, 'getStats']);
        Route::get('posts-categories', [PostController::class, 'getCategories']);
        Route::get('posts-tags', [PostController::class, 'getTags']);
    });

    Route::middleware('permission:create-posts')->group(function () {
        Route::post('posts', [PostController::class, 'store']);
    });

    Route::middleware('permission:edit-posts')->group(function () {
        Route::put('posts/{post}', [PostController::class, 'update']);
        Route::patch('posts/{post}/toggle-published', [PostController::class, 'togglePublished']);
        Route::post('posts/bulk-action', [PostController::class, 'bulkAction']);
    });

    Route::middleware('permission:delete-posts')->group(function () {
        Route::delete('posts/{post}', [PostController::class, 'destroy']);
    });

    // Download management routes
    Route::middleware('permission:view-downloads')->group(function () {
        Route::get('downloads', [DownloadController::class, 'index']);
        Route::get('downloads/{download}', [DownloadController::class, 'show']);
        Route::get('downloads-stats', [DownloadController::class, 'getStats']);
        Route::get('downloads-categories', [DownloadController::class, 'getCategories']);
    });

    Route::middleware('permission:create-downloads')->group(function () {
        Route::post('downloads', [DownloadController::class, 'store']);
    });

    Route::middleware('permission:edit-downloads')->group(function () {
        Route::put('downloads/{download}', [DownloadController::class, 'update']);
        Route::patch('downloads/{download}/toggle-published', [DownloadController::class, 'togglePublished']);
        Route::patch('downloads/{download}/toggle-featured', [DownloadController::class, 'toggleFeatured']);
        Route::post('downloads/bulk-action', [DownloadController::class, 'bulkAction']);
    });

    Route::middleware('permission:delete-downloads')->group(function () {
        Route::delete('downloads/{download}', [DownloadController::class, 'destroy']);
    });

    // Public download route
    Route::get('downloads/{download}/download', [DownloadController::class, 'downloadFile'])->name('api.downloads.download');

    // Contact management routes
    Route::middleware('permission:view-contacts')->group(function () {
        Route::get('contacts', [ContactController::class, 'index']);
        Route::get('contacts/{contact}', [ContactController::class, 'show']);
        Route::get('contacts-stats', [ContactController::class, 'getStats']);
        Route::get('contacts-export', [ContactController::class, 'export']);
    });

    Route::middleware('permission:manage-contacts')->group(function () {
        Route::put('contacts/{contact}', [ContactController::class, 'update']);
        Route::patch('contacts/{contact}/mark-read', [ContactController::class, 'markAsRead']);
        Route::patch('contacts/{contact}/mark-replied', [ContactController::class, 'markAsReplied']);
        Route::patch('contacts/{contact}/mark-spam', [ContactController::class, 'markAsSpam']);
        Route::patch('contacts/{contact}/archive', [ContactController::class, 'archive']);
        Route::post('contacts/bulk-action', [ContactController::class, 'bulkAction']);
    });

    Route::middleware('permission:delete-contacts')->group(function () {
        Route::delete('contacts/{contact}', [ContactController::class, 'destroy']);
    });

    // Test route
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles', 'permissions');
    });
});
