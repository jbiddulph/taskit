<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LtdTier extends Model
{
    protected $table = 'taskit_ltd_tiers';

    protected $fillable = [
        'name',
        'subscription_type',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function redemptionCodes(): HasMany
    {
        return $this->hasMany(RedemptionCode::class, 'ltd_tier_id');
    }
}


