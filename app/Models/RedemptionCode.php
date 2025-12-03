<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedemptionCode extends Model
{
    protected $table = 'taskit_redemption_codes';

    protected $fillable = [
        'code',
        'ltd_tier_id',
        'redeemed',
        'user_id',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed' => 'boolean',
        'redeemed_at' => 'datetime',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LtdTier::class, 'ltd_tier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}


