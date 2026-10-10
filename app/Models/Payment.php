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
    ];

    protected $casts = [
        'amount_paid'       => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'payment_date'      => 'date',
        'covered_months'    => 'array', // JSONB → PHP array
        'is_partial'        => 'boolean',
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

    // ─── Signed URL Access ────────────────────────────────────────────

    /**
     * Generate a time-limited signed URL for the receipt PDF.
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (! $this->receipt_pdf_path) {
            return null;
        }

        $disk = config('smartsyndic.uploads.storage_disk', 'public');
        if ($disk === 's3') {
            $ttl = config('smartsyndic.uploads.signed_url_ttl', 15);
            return Storage::disk('s3')->temporaryUrl($this->receipt_pdf_path, now()->addMinutes($ttl));
        }

        return url('storage/' . $this->receipt_pdf_path);
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

    // ─── Receipt Number Generator ─────────────────────────────────────

    /**
     * Generate a unique receipt number: REC-YYYY-XXXXXX
     */
    public static function generateReceiptNumber(): string
    {
        $prefix = config('smartsyndic.receipt.prefix', 'REC');
        $year   = now()->format('Y');
        $seq    = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$seq}";
    }
}
