<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory, HasUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'total_floors',
    ];

    protected $casts = [
        'total_floors' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * Get total number of apartment units in this building.
     */
    public function unitCount(): int
    {
        return $this->apartments()->count();
    }
}
