<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog — Immutable record of all privileged actions.
 *
 * This model is append-only. Updates and deletes are prohibited.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'actor_id',
        'action',
        'target_tenant_id',
        'ip_address',
        'user_agent',
        'metadata',
        'payment_snapshot', // Feature 3 — immutable receipt copy for dispute resolution
        'created_at',
    ];

    protected $casts = [
        'metadata'         => 'array',
        'payment_snapshot' => 'array', // Feature 3
        'created_at'       => 'datetime',
    ];

    // ─── Immutability Guards ──────────────────────────────────────────

    public static function boot(): void
    {
        parent::boot();

        // Prevent updates — audit logs are immutable
        static::updating(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        // Prevent deletes — audit logs are immutable
        static::deleting(function () {
            throw new \RuntimeException('Audit logs are immutable and cannot be deleted.');
        });
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'target_tenant_id');
    }

    // ─── Factory Method ───────────────────────────────────────────────

    /**
     * Record an audit log entry.
     */
    public static function record(
        string $actorId,
        string $action,
        ?string $targetTenantId = null,
        array $metadata = [],
        ?array $paymentSnapshot = null,
    ): self {
        return self::create([
            'actor_id'         => $actorId,
            'action'           => $action,
            'target_tenant_id' => $targetTenantId,
            'ip_address'       => request()->ip() ?? '0.0.0.0',
            'user_agent'       => request()->userAgent() ?? 'unknown',
            'metadata'         => $metadata,
            'payment_snapshot' => $paymentSnapshot, // Feature 3
            'created_at'       => now(),
        ]);
    }
}

