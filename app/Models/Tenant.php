<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Filament\Models\Contracts\HasName;

class Tenant extends Model implements HasName
{
    use HasFactory, HasUuid;

    public function getFilamentName(): string
    {
        return $this->name;
    }

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'property_type',
        'plan_type',
        'status',
        'trial_ends_at',
        'subscription_expires_at',
        'invitation_code',
        'bank_rib',
        'is_transparency_enabled',
    ];

    protected $casts = [
        'trial_ends_at'            => 'datetime',
        'subscription_expires_at'  => 'datetime',
        'is_transparency_enabled'  => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }

    // ─── Business Logic ───────────────────────────────────────────────

    /**
     * Check if the tenant's subscription or trial is currently valid.
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Check if tenant is in trial and trial has not expired.
     */
    public function isTrialValid(): bool
    {
        return $this->plan_type === 'TRIAL'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Generate a new cryptographically random invitation code.
     */
    public static function generateInvitationCode(): string
    {
        $length = config('smartsyndic.invitation_code_length', 8);
        return strtoupper(Str::random($length));
    }

    /**
     * Regenerate invitation code (invalidates the previous one).
     */
    public function regenerateInvitationCode(): self
    {
        $this->update(['invitation_code' => self::generateInvitationCode()]);
        return $this;
    }
}
