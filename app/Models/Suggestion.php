<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Suggestion extends Model
{
    use HasFactory, HasUuid, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'apartment_id',
        'title',
        'description',
        'attachment_url',
        'status',
        'admin_notes',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    // ─── Signed URL ───────────────────────────────────────────────────

    public function getAttachmentSignedUrlAttribute(): ?string
    {
        if (! $this->attachment_url) {
            return null;
        }

        $ttl = config('smartsyndic.uploads.signed_url_ttl', 15);
        return Storage::disk(config('smartsyndic.uploads.storage_disk'))
            ->temporaryUrl($this->attachment_url, now()->addMinutes($ttl));
    }

    // ─── Workflow ─────────────────────────────────────────────────────

    public function markAccepted(?string $notes = null): void
    {
        $this->update(['status' => 'ACCEPTED', 'admin_notes' => $notes]);
    }

    public function markRejected(?string $notes = null): void
    {
        $this->update(['status' => 'REJECTED', 'admin_notes' => $notes]);
    }

    public function convertToPoll(): void
    {
        $this->update(['status' => 'CONVERTED_TO_POLL']);
    }
}
