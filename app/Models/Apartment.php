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

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Apartment $apartment) {
            // 1. Fetch all residents linked to this apartment
            $residents = $apartment->residents()->get();

            // 2. Detach residents from this apartment
            $apartment->residents()->detach();

            // 3. Delete payments of this apartment first (cleans up FK references)
            $apartment->payments()->delete();

            // 4. Delete suggestions and poll votes of this apartment
            $apartment->suggestions()->delete();
            $apartment->pollVotes()->delete();

            // 5. Directly delete the resident accounts associated with this apartment
            foreach ($residents as $resident) {
                if ($resident->apartments()->count() === 0) {
                    \App\Models\Payment::where('recorded_by', $resident->id)->delete();
                    $resident->tokens()->delete();
                    $resident->suggestions()->delete();
                    $resident->pollVotes()->delete();
                    $resident->delete();
                }
            }
        });
    }

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

    public function tenant(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(Tenant::class, Building::class, 'id', 'id', 'building_id', 'tenant_id');
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
     * Clean unit number by stripping any redundant "شقة", "شقه", "الشقة", "Appartement" prefixes.
     */
    public static function cleanUnit(?string $unit): string
    {
        if ($unit === null) {
            return '';
        }
        $trimmed = trim($unit);
        $cleaned = preg_replace('/^(?:ال)?(?:شقة|شقه|Appartement|Apt\.?)\s*/iu', '', $trimmed);
        while ($cleaned !== $trimmed && $cleaned !== '') {
            $trimmed = trim($cleaned);
            $cleaned = preg_replace('/^(?:ال)?(?:شقة|شقه|Appartement|Apt\.?)\s*/iu', '', $trimmed);
        }
        return trim($cleaned);
    }

    /**
     * Format unit nicely with a single "شقة " prefix.
     */
    public static function formatUnit(?string $unit): string
    {
        $clean = self::cleanUnit($unit);
        if ($clean === '') {
            return 'شقة';
        }
        return 'شقة ' . $clean;
    }

    /**
     * Format definite unit nicely with "الشقة " prefix.
     */
    public static function formatDefiniteUnit(?string $unit): string
    {
        $clean = self::cleanUnit($unit);
        if ($clean === '') {
            return 'الشقة';
        }
        return 'الشقة ' . $clean;
    }

    public function getCleanUnitNumberAttribute(): string
    {
        return self::cleanUnit($this->unit_number);
    }

    public function getFormattedUnitAttribute(): string
    {
        return self::formatUnit($this->unit_number);
    }

    public function getDefiniteUnitAttribute(): string
    {
        return self::formatDefiniteUnit($this->unit_number);
    }

    /**
     * Calculate total arrears (unpaid months) for this apartment.
     */
    public function getDisplayLabelAttribute(): string
    {
        $buildingName = $this->building?->name ?? 'العمارة';
        return "{$buildingName} - {$this->formatted_unit}";
    }
}
