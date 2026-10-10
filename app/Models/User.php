<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant, HasName
{
    use HasFactory, HasUuid, HasApiTokens, Notifiable;

    protected $fillable = [
        'tenant_id',
        'role',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'locale',
        'theme_preference',
        'status',
        'fcm_device_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'fcm_device_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // ─── Filament Authorization ───────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'super-admin' => $this->isSuperAdmin(),
            'syndic'      => $this->isAdminSyndic() && $this->isActive(),
            default       => false,
        };
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->tenant ? collect([$this->tenant]) : collect();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenant_id === $tenant->id;
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->tenant;
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class, 'resident_apartments')
            ->withPivot('occupancy_type', 'is_primary_contact')
            ->withTimestamps();
    }

    public function residentApartments(): HasMany
    {
        return $this->hasMany(ResidentApartment::class);
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }

    public function isAdminSyndic(): bool
    {
        return $this->role === 'ADMIN_SYNDIC';
    }

    public function isResident(): bool
    {
        return $this->role === 'RESIDENT';
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    // ─── Accessors & Filament Contracts ──────────────────────────────

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Route notifications for FCM.
     */
    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_device_token;
    }
}
