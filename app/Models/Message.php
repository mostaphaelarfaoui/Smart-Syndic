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
}
