<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'subject',
        'message',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
        'read_at',
        'replied_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'replied_at' => 'datetime'
    ];

    protected $dates = [
        'read_at',
        'replied_at'
    ];

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeSpam($query)
    {
        return $query->where('status', 'spam');
    }

    public function scopeUnread($query)
    {
        return $query->whereIn('status', ['new']);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('company', 'like', "%{$term}%")
              ->orWhere('subject', 'like', "%{$term}%")
              ->orWhere('message', 'like', "%{$term}%");
        });
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            'new' => 'Nova',
            'read' => 'Lida',
            'replied' => 'Respondida',
            'archived' => 'Arquivada',
            'spam' => 'Spam'
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'new' => 'blue',
            'read' => 'yellow',
            'replied' => 'green',
            'archived' => 'gray',
            'spam' => 'red'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getIsUnreadAttribute()
    {
        return in_array($this->status, ['new']);
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getShortMessageAttribute()
    {
        return \Illuminate\Support\Str::limit($this->message, 100);
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    public function markAsReplied()
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => now()
        ]);
    }

    public function markAsArchived()
    {
        $this->update(['status' => 'archived']);
    }

    public function markAsSpam()
    {
        $this->update(['status' => 'spam']);
    }

    public function markAsNew()
    {
        $this->update([
            'status' => 'new',
            'read_at' => null,
            'replied_at' => null
        ]);
    }

    // Check if contact is from same IP (for spam detection)
    public static function countFromIp($ipAddress, $hours = 24)
    {
        return static::where('ip_address', $ipAddress)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    // Check if contact is from same email (for spam detection)
    public static function countFromEmail($email, $hours = 24)
    {
        return static::where('email', $email)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    // Auto-detect potential spam
    public function isPotentialSpam()
    {
        // Check for multiple contacts from same IP
        if (static::countFromIp($this->ip_address, 1) > 3) {
            return true;
        }

        // Check for multiple contacts from same email
        if (static::countFromEmail($this->email, 24) > 5) {
            return true;
        }

        // Check for suspicious keywords in message
        $spamKeywords = ['viagra', 'casino', 'lottery', 'winner', 'prize', 'click here', 'free money'];
        $message = strtolower($this->message);
        
        foreach ($spamKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    // Boot method for automatic spam detection
    protected static function boot()
    {
        parent::boot();

        static::created(function ($contact) {
            if ($contact->isPotentialSpam()) {
                $contact->markAsSpam();
            }
        });
    }
}

