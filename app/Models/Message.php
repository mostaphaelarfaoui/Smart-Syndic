<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'sender_id',
        'sender_role',
        'target_type',
        'subject',
        'body',
        'category',
        'priority',
        'target_metadata',
        'parent_id',
    ];

    protected $casts = [
        'target_metadata' => 'array',
    ];

    protected $appends = [
        'category_label',
        'priority_label',
        'sender_display_name',
        'sender_role_label',
        'sender_unit_label',
        'recipient_label',
        'recipient_name',
        'recipient_unit_label',
        'latest_activity_at',
        'latest_message_body',
        'is_read',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function recipientUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_recipients')
            ->withPivot('is_read', 'read_at', 'is_archived')
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function isFromSuperAdmin(): bool
    {
        return $this->sender_role === 'SUPER_ADMIN';
    }

    public function isFromSyndic(): bool
    {
        return in_array($this->sender_role, ['ADMIN_SYNDIC', 'SYNDIC'], true);
    }

    public function isFromResident(): bool
    {
        return $this->sender_role === 'RESIDENT';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'ANNOUNCEMENT' => 'إعلان رسمي',
            'INQUIRY'      => 'استفسار',
            'COMPLAINT'    => 'شكاية / تبليغ',
            'MAINTENANCE'  => 'صيانة وأشغال',
            'FINANCIAL'    => 'شؤون مالية',
            default        => 'عامة',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'URGENT' => '🚨 عاجل جداً',
            'HIGH'   => '⚠️ هام',
            default  => 'عادي',
        };
    }

    public function getSenderDisplayNameAttribute(): string
    {
        if ($this->isFromSuperAdmin()) {
            return 'الإدارة العامة للمنصة';
        }

        return $this->sender?->full_name ?? ($this->target_metadata['resident_name'] ?? 'مستخدم');
    }

    public function getSenderRoleLabelAttribute(): string
    {
        if ($this->isFromSuperAdmin()) {
            return 'الإدارة العامة للمنصة';
        }
        if ($this->isFromSyndic()) {
            return 'إدارة السانديك';
        }
        return 'ساكن مقيم';
    }

    public function getSenderUnitLabelAttribute(): ?string
    {
        if (! $this->isFromResident()) {
            return null;
        }

        $rawUnit = $this->target_metadata['unit_number'] ?? null;
        if (! $rawUnit && $this->relationLoaded('sender') && $this->sender) {
            $rawUnit = $this->sender->apartments?->first()?->clean_unit_number;
        }

        return $rawUnit ? format_unit_label($rawUnit) : null;
    }

    public function getRecipientLabelAttribute(): string
    {
        if ($this->target_type === 'ALL_RESIDENTS') {
            return 'كافة سكان الإقامة';
        }

        if (in_array($this->target_type, ['SYNDIC', 'SYNDICS_ONLY'], true)) {
            return 'إدارة السانديك';
        }

        if ($this->target_type === 'INDIVIDUAL') {
            $name = $this->target_metadata['recipient_name'] ?? null;
            $unit = $this->target_metadata['unit_number'] ?? null;
            if (! $name && $this->relationLoaded('recipients') && $this->recipients->first()?->user) {
                $user = $this->recipients->first()->user;
                $name = $user->full_name;
                $unit = $user->apartments?->first()?->clean_unit_number;
            }
            if ($name && $unit) {
                return $name . ' (' . format_unit_label($unit) . ')';
            }
            return $name ?: 'ساكن محدد';
        }

        if ($this->target_type === 'GROUP') {
            $units = $this->target_metadata['targeted_units'] ?? [];
            if (! empty($units)) {
                $formatted = array_map(fn($u) => format_unit_label($u), (array) $units);
                return 'مجموعة شقق: ' . implode(', ', $formatted);
            }
            return 'مجموعة شقق محددة';
        }

        if (in_array($this->target_type, ['RESIDENTS_ONLY', 'SPECIFIC_TENANT'], true)) {
            return 'سكان الإقامة';
        }

        return $this->target_metadata['recipient_label'] ?? 'المستلم';
    }

    public function getRecipientNameAttribute(): ?string
    {
        if ($this->target_type === 'INDIVIDUAL') {
            return $this->target_metadata['recipient_name'] ?? $this->recipients->first()?->user?->full_name;
        }
        return null;
    }

    public function getRecipientUnitLabelAttribute(): ?string
    {
        if ($this->target_type === 'INDIVIDUAL') {
            $unit = $this->target_metadata['unit_number'] ?? null;
            if (! $unit && $this->relationLoaded('recipients') && $this->recipients->first()?->user) {
                $unit = $this->recipients->first()->user->apartments?->first()?->clean_unit_number;
            }
            return $unit ? format_unit_label($unit) : null;
        }
        return null;
    }

    public function getLatestActivityAtAttribute()
    {
        if ($this->relationLoaded('replies') && $this->replies->isNotEmpty()) {
            return $this->replies->last()->created_at;
        }

        return $this->replies()->latest('created_at')->value('created_at') ?? $this->created_at;
    }

    public function getLatestMessageBodyAttribute(): string
    {
        if ($this->relationLoaded('replies') && $this->replies->isNotEmpty()) {
            return $this->replies->last()->body;
        }

        return $this->body;
    }

    public function getIsReadAttribute(): bool
    {
        $userId = auth()->id();
        if (! $userId) {
            return true;
        }

        return ! $this->isUnreadForUser($userId);
    }

    public function isUnreadForUser(string $userId): bool
    {
        // Check recipient record on root message
        if ($this->relationLoaded('recipients')) {
            $parentRec = $this->recipients->firstWhere('user_id', $userId);
            if ($parentRec && ! $parentRec->is_read) {
                return true;
            }
        } else {
            $hasUnreadParent = $this->recipients()->where('user_id', $userId)->where('is_read', false)->exists();
            if ($hasUnreadParent) {
                return true;
            }
        }

        // Check recipient records on all replies
        if ($this->relationLoaded('replies')) {
            foreach ($this->replies as $reply) {
                if ($reply->relationLoaded('recipients')) {
                    $replyRec = $reply->recipients->firstWhere('user_id', $userId);
                    if ($replyRec && ! $replyRec->is_read) {
                        return true;
                    }
                } else {
                    if ($reply->recipients()->where('user_id', $userId)->where('is_read', false)->exists()) {
                        return true;
                    }
                }
            }
        } else {
            if ($this->replies()->whereHas('recipients', fn ($r) => $r->where('user_id', $userId)->where('is_read', false))->exists()) {
                return true;
            }
        }

        return false;
    }
}
