<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'poll_id',
        'option_text',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    /**
     * Get vote count for this option.
     */
    public function voteCount(): int
    {
        return $this->votes()->count();
    }

    /**
     * Get vote percentage for this option relative to the total poll votes.
     */
    public function votePercentage(): float
    {
        $total = $this->poll->totalVotes();
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->voteCount() / $total) * 100, 1);
    }
}
