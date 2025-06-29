<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'phone',
        'website',
        'location',
        'is_active',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime'
    ];

    protected $dates = [
        'last_login_at'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAdmins($query)
    {
        return $query->role('admin');
    }

    public function scopeEditors($query)
    {
        return $query->role(['admin', 'editor']);
    }

    // Accessors
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        // Generate Gravatar URL as fallback
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getInitialsAttribute()
    {
        $names = explode(' ', $this->name);
        $initials = '';
        
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }

    // Methods
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isEditor()
    {
        return $this->hasRole(['admin', 'editor']);
    }

    public function canEdit()
    {
        return $this->hasRole(['admin', 'editor']);
    }

    public function canDelete()
    {
        return $this->hasRole('admin');
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    public function downloads()
    {
        return $this->hasMany(Download::class, 'user_id');
    }

    // Statistics
    public function getStatsAttribute()
    {
        return [
            'posts_count' => 0, // Simplified for now
            'published_posts_count' => 0,
            'projects_count' => 0,
            'downloads_count' => 0,
            'total_post_views' => 0,
            'total_downloads' => 0
        ];
    }
}
