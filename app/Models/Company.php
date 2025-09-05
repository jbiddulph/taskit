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
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_ends_at',
        'subscription_status',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
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

    /**
     * Get projects for this company through users
     */
    public function projects()
    {
        return Project::whereHas('owner', function ($query) {
            $query->where('company_id', $this->id);
        })->where('is_active', true);
    }

    /**
     * Get visible projects based on subscription tier
     */
    public function visibleProjects()
    {
        $query = $this->projects()->orderBy('viewing_order');
        
        // Apply subscription-based limits
        $limit = $this->getProjectLimit();
        if ($limit !== PHP_INT_MAX) {
            $query->limit($limit);
        }
        
        return $query;
    }

    /**
     * Get member limit based on subscription type
     */
    public function getMemberLimit(): int
    {
        return match($this->subscription_type) {
            'FREE' => 5,
            'MIDI' => 10,
            'MAXI' => PHP_INT_MAX, // Unlimited
            default => 5
        };
    }

    /**
     * Get project limit based on subscription type
     */
    public function getProjectLimit(): int
    {
        return match($this->subscription_type) {
            'FREE' => 10,
            'MIDI' => 20,
            'MAXI' => PHP_INT_MAX, // Unlimited
            default => 10
        };
    }

    /**
     * Check if company can accept new members
     */
    public function canAcceptNewMembers(): bool
    {
        $currentMemberCount = $this->users()->count();
        return $currentMemberCount < $this->getMemberLimit();
    }

    /**
     * Check if company can create new projects
     */
    public function canCreateNewProjects(): bool
    {
        $currentProjectCount = $this->projects()->count();
        return $currentProjectCount < $this->getProjectLimit();
    }

    /**
     * Get current member count
     */
    public function getCurrentMemberCount(): int
    {
        return $this->users()->count();
    }

    /**
     * Get current project count
     */
    public function getCurrentProjectCount(): int
    {
        return $this->projects()->count();
    }

    /**
     * Check if company is near member limit (at warning threshold)
     */
    public function isNearMemberLimit(): bool
    {
        $current = $this->getCurrentMemberCount();
        $limit = $this->getMemberLimit();
        
        // Show warning at limit - 1 (4 for FREE, 9 for MIDI)
        return $current >= ($limit - 1);
    }

    /**
     * Check if company is near project limit (at warning threshold)
     */
    public function isNearProjectLimit(): bool
    {
        $current = $this->getCurrentProjectCount();
        $limit = $this->getProjectLimit();
        
        // Show warning at 80% of limit
        return $current >= ($limit * 0.8);
    }
}
