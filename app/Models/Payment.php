<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    use HasFactory, HasUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'apartment_id',
        'recorded_by',
        'amount_paid',
        'payment_method',
        'cheque_reference',
        'payment_date',
        'covered_months',
        'is_partial',
        'remaining_balance',
        'receipt_number',
        'receipt_pdf_path',
        'bank_receipt_proof',
        // Feature 12 — Payment workflow
        'status',
        'confirmed_at',
        'sent_to_resident_at',
    ];

    protected $casts = [
        'amount_paid'         => 'decimal:2',
        'remaining_balance'   => 'decimal:2',
        'payment_date'        => 'date',
        'covered_months'      => 'array', // JSONB → PHP array
        'is_partial'          => 'boolean',
        'confirmed_at'        => 'datetime',
        'sent_to_resident_at' => 'datetime',
    ];

    protected $appends = [
        'receipt_url',
        'bank_proof_url',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the resident associated with this payment via the apartment.
     */
    public function getResidentAttribute(): ?User
    {
        return $this->apartment?->residents?->first();
    }

    // ─── Signed URL Access ────────────────────────────────────────────

    /**
     * Generate a cryptographic token for secure direct receipt download.
     */
    public function getReceiptToken(): string
    {
        return substr(hash_hmac('sha256', $this->id . '|' . ($this->created_at?->timestamp ?? '') . '|' . config('app.key'), config('app.key')), 0, 32);
    }

    /**
     * Generate a secure direct URL for the receipt PDF.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->id) {
            return null;
        }

        $token = $this->getReceiptToken();

        return url('/receipts/' . $this->id . '/download?token=' . $token);
    }

    /**
     * Generate a time-limited signed URL for the bank receipt proof.
     */
    public function getBankProofUrlAttribute(): ?string
    {
        if (! $this->bank_receipt_proof) {
            return null;
        }

        $disk = config('smartsyndic.uploads.storage_disk', 'public');
        if ($disk === 's3') {
            $ttl = config('smartsyndic.uploads.signed_url_ttl', 15);
            return Storage::disk('s3')->temporaryUrl($this->bank_receipt_proof, now()->addMinutes($ttl));
        }

        return url('storage/' . $this->bank_receipt_proof);
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    /** Feature 12 — Only DRAFT payments (editable). */
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    /** Feature 12 — Only CONFIRMED payments (locked, not yet sent). */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'CONFIRMED');
    }

    /** Feature 12 — Only payments sent to resident. */
    public function scopeSentToResident($query)
    {
        return $query->where('status', 'SENT_TO_RESIDENT');
    }

    // ─── Status Helpers ───────────────────────────────────────────────

    public function isDraft(): bool          { return $this->status === 'DRAFT'; }
    public function isConfirmed(): bool      { return $this->status === 'CONFIRMED'; }
    public function isSentToResident(): bool { return $this->status === 'SENT_TO_RESIDENT'; }

    // ─── Receipt Number Generator ─────────────────────────────────────

    /**
     * @deprecated Use Tenant::nextReceiptNumber() — atomic & sequential (Feature 13).
     * Kept for backward compatibility only. Uses random_int instead of mt_rand.
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = config('smartsyndic.receipt.prefix', 'REC');
        $year   = now()->format('Y');
        $seq    = str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$seq}";
    }
}

