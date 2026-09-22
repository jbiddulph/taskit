<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalObjectPhoto extends Model
{
    protected $table = 'taskit_operational_object_photos';

    protected $fillable = [
        'operational_object_id',
        'uploaded_by_user_id',
        'filename',
        'original_filename',
        'mime_type',
        'file_path',
        'file_size',
        'caption',
        'sort_order',
        'is_cover',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'is_cover' => 'boolean',
    ];

    public function operationalObject(): BelongsTo
    {
        return $this->belongsTo(OperationalObject::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
