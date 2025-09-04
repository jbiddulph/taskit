<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $table = 'taskit_companies';
    
    protected $fillable = [
        'name',
        'code',
        'subscription_type',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($company) {
            if (empty($company->code)) {
                $company->code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());
        
        return $code;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
