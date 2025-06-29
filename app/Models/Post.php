<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'full_content',
        'author',
        'publish_date',
        'category',
        'tags',
        'image_url',
        'is_published',
        'views_count'
    ];

    protected $casts = [
        'tags' => 'array',
        'publish_date' => 'date',
        'is_published' => 'boolean',
        'views_count' => 'integer'
    ];

    protected $dates = [
        'publish_date'
    ];

    // Automatically generate slug from title
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByAuthor($query, $author)
    {
        return $query->where('author', $author);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('excerpt', 'like', "%{$term}%")
              ->orWhere('full_content', 'like', "%{$term}%");
        });
    }

    // Accessors
    public function getExcerptAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }
        
        // Generate excerpt from content if not provided
        return Str::limit(strip_tags($this->full_content), 150);
    }

    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->full_content));
        $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute
        return $readingTime;
    }

    // Mutators
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Increment views
    public function incrementViews()
    {
        $this->increment('views_count');
    }
}
