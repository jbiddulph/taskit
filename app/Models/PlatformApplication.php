<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformApplication extends Model
{
    protected $table = 'taskit_applications';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'taskit_company_applications',
            'application_id',
            'company_id'
        )->withPivot(['enabled', 'subscription_plan'])->withTimestamps();
    }

    public function companyApplications(): HasMany
    {
        return $this->hasMany(CompanyApplication::class, 'application_id');
    }
}
