<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionPhoto extends Model
{
    protected $table = 'taskit_inspection_photos';

    protected $fillable = [
        'inspection_id',
        'item_key',
        'filename',
        'original_filename',
        'mime_type',
        'file_path',
        'file_size',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
