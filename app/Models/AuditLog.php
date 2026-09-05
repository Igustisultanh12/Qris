<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'customer_id',
        'role',
        'action',
        'entity',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function record(
        string $action,
        string $entity,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $user = auth()->user();

        return static::create([
            'user_id' => $user?->id,
            'customer_id' => $user?->customer_id,
            'role' => $user?->isSuperAdmin() ? 'admin' : ($user ? 'customer' : 'system'),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'request_id' => request()->header('X-Request-ID'),
            'created_at' => now(),
        ]);
    }
}
