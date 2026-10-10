<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use HasFactory, HasUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'created_by',
        'title',
        'description',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isOpen(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Check if a given apartment has already voted on this poll.
     */
    public function hasApartmentVoted(string $apartmentId): bool
    {
        return $this->votes()->where('apartment_id', $apartmentId)->exists();
    }

    /**
     * Get total vote count.
     */
    public function totalVotes(): int
    {
        return $this->votes()->count();
    }
}
