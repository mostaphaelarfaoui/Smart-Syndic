<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'content',
        'priority',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    protected $appends = [
        'priority_label',
        'creator_name',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'URGENT' => 'عاجل',
            'INFO'   => 'تنبيه هام',
            default  => 'عادي',
        };
    }

    public function getCreatorNameAttribute(): string
    {
        return $this->creator?->full_name ?? 'إدارة السانديك';
    }
}
