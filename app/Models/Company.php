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
        'scheduled_subscription_type',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_ends_at',
        'scheduled_change_date',
        'subscription_status',
        'logo_url',
        'prune_completed_tasks',
        'subdomain',
        'subdomain_url',
        'is_public',
    ];

    protected $casts = [
        'subscription_ends_at' => 'datetime',
        'scheduled_change_date' => 'datetime',
        'prune_completed_tasks' => 'boolean',
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
     * Get todos for this company through users
     */
    public function todos()
    {
        return \App\Models\Todo::whereHas('user', function ($query) {
            $query->where('company_id', $this->id);
        });
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
     * Get accessible users based on subscription tier (first N registered)
     */
    public function accessibleUsers()
    {
        $query = $this->users()->orderBy('created_at');
        
        // Apply subscription-based limits
        $limit = $this->getMemberLimit();
        if ($limit !== PHP_INT_MAX) {
            $query->limit($limit);
        }
        
        return $query;
    }

    /**
     * Check if a specific user can access the system based on subscription limits
     */
    public function userCanAccess(User $user): bool
    {
        if ($user->company_id !== $this->id) {
            return false;
        }
        
        // Get the user IDs of accessible users (first N registered)
        $accessibleUserIds = $this->accessibleUsers()->pluck('id')->toArray();
        
        return in_array($user->id, $accessibleUserIds);
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

    /**
     * Get the effective subscription type (current until scheduled change takes effect)
     */
    public function getEffectiveSubscriptionType(): string
    {
        // If there's a scheduled change and it hasn't taken effect yet, use current subscription
        if ($this->scheduled_subscription_type && 
            $this->scheduled_change_date && 
            $this->scheduled_change_date->isFuture()) {
            return $this->subscription_type;
        }
        
        // If scheduled change date has passed, the scheduled type should have become active
        if ($this->scheduled_subscription_type && 
            $this->scheduled_change_date && 
            $this->scheduled_change_date->isPast()) {
            return $this->scheduled_subscription_type;
        }
        
        return $this->subscription_type;
    }

    /**
     * Check if there's a pending subscription change
     */
    public function hasPendingSubscriptionChange(): bool
    {
        return $this->scheduled_subscription_type && 
               $this->scheduled_change_date && 
               $this->scheduled_change_date->isFuture();
    }

    /**
     * Get pending subscription change info
     */
    public function getPendingSubscriptionChange(): ?array
    {
        if (!$this->hasPendingSubscriptionChange()) {
            return null;
        }
        
        return [
            'current_plan' => $this->subscription_type,
            'scheduled_plan' => $this->scheduled_subscription_type,
            'change_date' => $this->scheduled_change_date,
            'is_downgrade' => $this->isDowngrade($this->subscription_type, $this->scheduled_subscription_type),
        ];
    }

    /**
     * Check if plan change is a downgrade
     */
    protected function isDowngrade(string $currentPlan, string $newPlan): bool
    {
        $planHierarchy = ['FREE' => 0, 'MIDI' => 1, 'MAXI' => 2];
        return ($planHierarchy[$newPlan] ?? 0) < ($planHierarchy[$currentPlan] ?? 0);
    }

    /**
     * Schedule a subscription change
     */
    public function scheduleSubscriptionChange(string $newPlan, \Carbon\Carbon $changeDate): void
    {
        $this->update([
            'scheduled_subscription_type' => $newPlan,
            'scheduled_change_date' => $changeDate,
        ]);
    }

    /**
     * Apply scheduled subscription change (called by webhook or scheduled job)
     */
    public function applyScheduledChange(): bool
    {
        if (!$this->scheduled_subscription_type || !$this->scheduled_change_date) {
            return false;
        }

        // Only apply if the scheduled date has passed
        if ($this->scheduled_change_date->isFuture()) {
            return false;
        }

        $this->update([
            'subscription_type' => $this->scheduled_subscription_type,
            'scheduled_subscription_type' => null,
            'scheduled_change_date' => null,
        ]);

        return true;
    }
}
