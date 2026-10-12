<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
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
        // Feature 1 — Syndic stamp image
        'stamp_image_path',
        // Feature 9 — Opening treasury and bank balances
        'initial_treasury_balance',
        'initial_bank_balance',
        'initial_balance_locked',
        // Feature 13 — Sequential receipt counter
        'receipt_sequence',
        // Feature 14 — Admin contact info for pending syndics
        'contact_info',
    ];

    protected $casts = [
        'trial_ends_at'            => 'datetime',
        'subscription_expires_at'  => 'datetime',
        'is_transparency_enabled'  => 'boolean',
        'initial_treasury_balance' => 'decimal:2',
        'initial_bank_balance'     => 'decimal:2',
        'initial_balance_locked'   => 'boolean',
        'contact_info'             => 'array',
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

    // ─── Feature 1: Stamp Image ───────────────────────────────────────

    /**
     * Return a public/signed URL for the syndic stamp image.
     */
    public function getStampUrlAttribute(): ?string
    {
        if (! $this->stamp_image_path) {
            return null;
        }

        $disk = config('smartsyndic.uploads.storage_disk', 'public');
        if ($disk === 's3') {
            $ttl = config('smartsyndic.uploads.signed_url_ttl', 15);
            return Storage::disk('s3')->temporaryUrl($this->stamp_image_path, now()->addMinutes($ttl));
        }

        return url('storage/' . $this->stamp_image_path);
    }

    // ─── Feature 13: Atomic Receipt Sequence ─────────────────────────

    /**
     * Increment and return the next sequential receipt number for this tenant.
     * Uses DB-level atomic increment to prevent duplicates under concurrency.
     *
     * Format: REC-{YEAR}-{TENANT_PREFIX}-{PADDED_SEQ}
     * Example: REC-2026-ALF-000042
     */
    public function nextReceiptNumber(): string
    {
        // Atomic DB increment — safe under concurrent requests
        \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $this->id)
            ->increment('receipt_sequence');

        // Reload to get the incremented value
        $this->refresh();

        $prefix = config('smartsyndic.receipt.prefix', 'REC');
        $year   = now()->format('Y');
        // Use first 3 letters of tenant name as identifier
        $tenantPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->name), 0, 3)) ?: 'ORG';
        $seq    = str_pad($this->receipt_sequence, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$tenantPrefix}-{$seq}";
    }
}
