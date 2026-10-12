<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'building_id',
        'unit_number',
        'floor',
        'monthly_fee',
        'is_billable',  // Feature 4 & 5: toggle billing for vacant/developer units
    ];

    protected $casts = [
        'floor'       => 'integer',
        'monthly_fee' => 'decimal:2',
        'is_billable' => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────

    /**
     * Feature 4 & 5: Only include apartments that should be billed.
     * Excludes developer-owned vacant units (is_billable = false).
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function residents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'resident_apartments')
            ->withPivot('occupancy_type', 'is_primary_contact')
            ->withTimestamps();
    }

    public function activeResidents(): BelongsToMany
    {
        return $this->residents()->where('users.status', 'ACTIVE');
    }

    public function residentApartments(): HasMany
    {
        return $this->hasMany(ResidentApartment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    // ─── Computed Properties ──────────────────────────────────────────

    /**
     * Get the primary contact resident for this apartment.
     */
    public function primaryContact(): ?User
    {
        return $this->residents()
            ->wherePivot('is_primary_contact', true)
            ->first();
    }

    /**
     * Calculate total arrears (unpaid months) for this apartment.
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->building->name} - {$this->unit_number}";
    }
}
