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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique(); // Títulos devem ser únicos
            $table->string('slug')->unique(); // Para URLs amigáveis
            $table->text('excerpt')->nullable(); // Pequeno resumo
            $table->longText('full_content'); // Conteúdo completo do artigo
            $table->string('author');
            $table->date('publish_date');
            $table->string('category')->nullable();
            $table->json('tags')->nullable(); // Array de strings armazenado como JSON
            $table->string('image_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->integer('views_count')->default(0);
            $table->timestamps();
            
            // Índices para performance
            $table->index(['is_published', 'publish_date']);
            $table->index('category');
            $table->index('author');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
