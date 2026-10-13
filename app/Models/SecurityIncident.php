<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncident extends Model
{
    protected $table = 'security_incidents';

    protected $fillable = [
        'incident_type',
        'severity',
        'ip_address',
        'user_agent',
        'target_email',
        'tenant_id',
        'details',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public static function log(
        string $type,
        string $severity = 'MEDIUM',
        ?string $details = null,
        ?string $targetEmail = null,
        ?string $tenantId = null
    ): self {
        $ip = request()?->ip() ?? '127.0.0.1';
        $ua = request()?->userAgent() ?? 'System';

        return static::create([
            'incident_type' => $type,
            'severity'      => $severity,
            'ip_address'    => $ip,
            'user_agent'    => substr($ua, 0, 500),
            'target_email'  => $targetEmail,
            'tenant_id'     => $tenantId,
            'details'       => $details,
            'status'        => 'OPEN',
        ]);
    }
}
