<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'full_description',
        'client',
        'category',
        'technologies',
        'project_url',
        'repository_url',
        'gallery',
        'featured_image',
        'start_date',
        'end_date',
        'status',
        'is_featured',
        'is_published',
        'views_count'
    ];

    protected $casts = [
        'technologies' => 'array',
        'gallery' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'views_count' => 'integer'
    ];

    protected $dates = [
        'start_date',
        'end_date'
    ];

    // Automatically generate slug from title
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->title);
            }
        });

        static::updating(function ($project) {
            if ($project->isDirty('title') && empty($project->slug)) {
                $project->slug = Str::slug($project->title);
            }
        });
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('full_description', 'like', "%{$term}%")
              ->orWhere('client', 'like', "%{$term}%");
        });
    }

    // Accessors
    public function getDurationAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        
        if ($this->start_date && $this->status === 'in_progress') {
            return $this->start_date->diffInDays(now());
        }
        
        return null;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'planning' => 'Planeamento',
            'in_progress' => 'Em Progresso',
            'completed' => 'Concluído',
            'on_hold' => 'Em Pausa'
        ];

        return $labels[$this->status] ?? $this->status;
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

    // Check if project has gallery images
    public function hasGallery()
    {
        return !empty($this->gallery) && is_array($this->gallery);
    }

    // Get gallery count
    public function getGalleryCountAttribute()
    {
        return $this->hasGallery() ? count($this->gallery) : 0;
    }
}

