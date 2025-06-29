<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'file_name',
        'file_path',
        'file_url',
        'file_size',
        'file_type',
        'mime_type',
        'category',
        'tags',
        'author',
        'version',
        'is_featured',
        'is_published',
        'requires_registration',
        'download_count'
    ];

    protected $casts = [
        'tags' => 'array',
        'file_size' => 'integer',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'requires_registration' => 'boolean',
        'download_count' => 'integer'
    ];

    // Automatically generate slug from title
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($download) {
            if (empty($download->slug)) {
                $download->slug = Str::slug($download->title);
            }
        });

        static::updating(function ($download) {
            if ($download->isDirty('title') && empty($download->slug)) {
                $download->slug = Str::slug($download->title);
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

    public function scopeByFileType($query, $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    public function scopeByAuthor($query, $author)
    {
        return $query->where('author', $author);
    }

    public function scopeRequiresRegistration($query, $requires = true)
    {
        return $query->where('requires_registration', $requires);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('file_name', 'like', "%{$term}%");
        });
    }

    // Accessors
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getFileTypeIconAttribute()
    {
        $extension = strtolower($this->file_extension);
        
        $icons = [
            'pdf' => 'file-text',
            'doc' => 'file-text',
            'docx' => 'file-text',
            'xls' => 'file-spreadsheet',
            'xlsx' => 'file-spreadsheet',
            'ppt' => 'file-presentation',
            'pptx' => 'file-presentation',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'mp4' => 'video',
            'avi' => 'video',
            'mov' => 'video',
            'mp3' => 'music',
            'wav' => 'music',
            'zip' => 'archive',
            'rar' => 'archive',
        ];

        return $icons[$extension] ?? 'file';
    }

    // Mutators
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    public function setFileSizeAttribute($value)
    {
        // Ensure file size is stored as integer (bytes)
        $this->attributes['file_size'] = (int) $value;
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // Increment download count
    public function incrementDownloads()
    {
        $this->increment('download_count');
    }

    // Check if file exists
    public function fileExists()
    {
        return file_exists(storage_path('app/' . $this->file_path));
    }

    // Get download URL
    public function getDownloadUrlAttribute()
    {
        return route('api.downloads.download', $this->slug);
    }

    // Check if user can download (based on registration requirement)
    public function canDownload($user = null)
    {
        if (!$this->is_published) {
            return false;
        }

        if ($this->requires_registration && !$user) {
            return false;
        }

        return true;
    }
}

