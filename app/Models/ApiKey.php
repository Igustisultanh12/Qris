<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer, SoftDeletes;

    protected $fillable = [
        'uuid',
        'customer_id',
        'name',
        'key_prefix',
        'key_hash',
        'secret_hash',
        'ip_whitelist',
        'rate_limit_per_minute',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = [
        'key_hash',
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'rate_limit_per_minute' => 'integer',
        ];
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Generate a new API Key and Secret pair securely.
     */
    public static function generate(
        Customer $customer,
        string $name,
        int $rateLimit = 60,
        ?string $ipWhitelist = null,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $keyPrefix = 'ka_live_';
        $randomKey = Str::random(24);
        $plainKey = $keyPrefix . $randomKey;
        $plainSecret = 'kas_' . Str::random(32);

        $apiKey = static::create([
            'customer_id' => $customer->id,
            'name' => $name,
            'key_prefix' => substr($plainKey, 0, 16) . '...',
            'key_hash' => hash('sha256', $plainKey),
            'secret_hash' => hash('sha256', $plainSecret),
            'rate_limit_per_minute' => $rateLimit,
            'ip_whitelist' => $ipWhitelist,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $plainKey,
            'plain_secret' => $plainSecret,
        ];
    }

    /**
     * Check if key is valid (active and not expired).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if given IP is allowed.
     */
    public function isIpAllowed(?string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true;
        }

        if (empty($ip)) {
            return false;
        }

        $allowedIps = array_map('trim', explode(',', $this->ip_whitelist));
        return in_array($ip, $allowedIps, true);
    }
}
