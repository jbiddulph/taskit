<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'taskit_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
        'mentioned_user_id',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'user_id' => 'integer',
        'mentioned_user_id' => 'integer',
    ];

    /**
     * Boot method to set up event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set is_read to false for new notifications
        static::creating(function ($notification) {
            if (!isset($notification->is_read)) {
                $notification->is_read = false;
            }
        });

        // Ensure user_id is always an integer before saving (for Supabase Realtime filtering)
        static::creating(function ($notification) {
            if (isset($notification->user_id)) {
                $notification->user_id = (int) $notification->user_id;
            }
            if (isset($notification->mentioned_user_id)) {
                $notification->mentioned_user_id = (int) $notification->mentioned_user_id;
            }
        });

        // Log notification creation for debugging
        static::created(function ($notification) {
            \Log::info('Notification created successfully', [
                'id' => $notification->id,
                'user_id' => $notification->user_id,
                'user_id_type' => gettype($notification->user_id),
                'type' => $notification->type,
                'title' => $notification->title,
                'mentioned_user_id' => $notification->mentioned_user_id ?? null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);
        });
    }

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}