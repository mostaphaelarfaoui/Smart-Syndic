<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'message',
        'type',
        'target_audience',
        'tenant_id',
        'is_active',
        'is_dismissible',
        'starts_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at'      => 'datetime',
        'expires_at'     => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope active announcements matching current context
     */
    public function scopeCurrentlyActive($query, ?string $userRole = null, ?string $tenantId = null)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) use ($userRole, $tenantId) {
                $q->where('target_audience', 'ALL');

                if ($userRole === 'SYNDIC' || $userRole === 'ADMIN_SYNDIC') {
                    $q->orWhere('target_audience', 'SYNDICS_ONLY');
                } elseif ($userRole === 'RESIDENT') {
                    $q->orWhere('target_audience', 'RESIDENTS_ONLY');
                } elseif ($userRole === 'SUPER_ADMIN') {
                    $q->orWhereIn('target_audience', ['SYNDICS_ONLY', 'RESIDENTS_ONLY', 'SPECIFIC_TENANT']);
                }

                if ($tenantId) {
                    $q->orWhere(function ($sub) use ($tenantId) {
                        $sub->where('target_audience', 'SPECIFIC_TENANT')
                            ->where('tenant_id', $tenantId);
                    });
                }
            });
    }
}
