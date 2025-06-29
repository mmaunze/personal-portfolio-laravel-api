<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_url');
            $table->bigInteger('file_size'); // em bytes
            $table->string('file_type');
            $table->string('mime_type');
            $table->string('category');
            $table->json('tags')->nullable();
            $table->string('author');
            $table->string('version')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('requires_registration')->default(false);
            $table->integer('download_count')->default(0);
            $table->timestamps();
            
            // Índices
            $table->index(['is_published', 'is_featured']);
            $table->index('category');
            $table->index('file_type');
            $table->index('author');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
