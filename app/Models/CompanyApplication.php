<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyApplication extends Model
{
    protected $table = 'taskit_company_applications';

    protected $fillable = [
        'company_id',
        'application_id',
        'enabled',
        'subscription_plan',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(PlatformApplication::class, 'application_id');
    }
}
