<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $table = 'taskit_api_keys';

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'key_prefix',
        'key_hash',
        'permissions',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
    ];

    public const DEFAULT_PERMISSIONS = [
        'tasks.read',
        'tasks.write',
        'assets.read',
        'assets.write',
        'users.read',
        'workspaces.read',
        'workspaces.write',
        'projects.read',
        'documents.write',
        'automations.read',
        'automations.write',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? self::DEFAULT_PERMISSIONS;
        $normalized = str_replace(':', '.', $permission);

        foreach ($permissions as $allowed) {
            $allowedNormalized = str_replace(':', '.', (string) $allowed);
            if ($allowedNormalized === '*' || $allowedNormalized === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{model: self, plain_text_key: string}
     */
    public static function generate(Company $company, string $name, ?User $creator = null, ?array $permissions = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $secret = Str::random(32);
        $plainTextKey = 'zt_live_'.$secret;
        $prefix = substr($plainTextKey, 0, 12);

        $model = static::create([
            'company_id' => $company->id,
            'created_by' => $creator?->id,
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => hash('sha256', $plainTextKey),
            'permissions' => $permissions ?? self::DEFAULT_PERMISSIONS,
            'expires_at' => $expiresAt,
        ]);

        return [
            'model' => $model,
            'plain_text_key' => $plainTextKey,
        ];
    }

    public static function findByPlainTextKey(string $plainTextKey): ?self
    {
        if (! str_starts_with($plainTextKey, 'zt_live_')) {
            return null;
        }

        $hash = hash('sha256', $plainTextKey);

        return static::query()
            ->where('key_hash', $hash)
            ->first();
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
