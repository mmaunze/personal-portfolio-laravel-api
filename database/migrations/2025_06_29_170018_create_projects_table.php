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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->text('description');
            $table->longText('full_description')->nullable();
            $table->string('client')->nullable();
            $table->string('category');
            $table->json('technologies')->nullable(); // Array de tecnologias
            $table->string('project_url')->nullable();
            $table->string('repository_url')->nullable();
            $table->json('gallery')->nullable(); // Array de URLs de imagens
            $table->string('featured_image')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning', 'in_progress', 'completed', 'on_hold'])->default('planning');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('views_count')->default(0);
            $table->timestamps();
            
            // Índices
            $table->index(['is_published', 'is_featured']);
            $table->index('category');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
