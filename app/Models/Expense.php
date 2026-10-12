<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use HasFactory, HasUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'building_id',
        'category',
        'title',
        'amount',
        'payment_method', // Feature 11: CASH, BANK_TRANSFER, CHEQUE
        'expense_date',
        'invoice_proof_path',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected $appends = ['invoice_proof_url'];

    // ─── Relationships ────────────────────────────────────────────────

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    // ─── Signed URL ───────────────────────────────────────────────────

    public function getInvoiceProofUrlAttribute(): ?string
    {
        if (! $this->invoice_proof_path) {
            return null;
        }

        $disk = config('smartsyndic.uploads.storage_disk', 'public');
        if ($disk === 's3') {
            $ttl = config('smartsyndic.uploads.signed_url_ttl', 15);
            return Storage::disk('s3')->temporaryUrl($this->invoice_proof_path, now()->addMinutes($ttl));
        }

        return url('storage/' . $this->invoice_proof_path);
    }
}
