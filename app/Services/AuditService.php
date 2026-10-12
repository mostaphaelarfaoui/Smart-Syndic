<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * AuditService — Centralized audit trail logging.
 *
 * All privileged actions (tenant operations, payment mutations,
 * account state changes) must be recorded through this service.
 */
class AuditService
{
    /**
     * Log an auditable action.
     */
    public function log(
        string $actorId,
        string $action,
        ?string $targetTenantId = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::record($actorId, $action, $targetTenantId, $metadata);
    }

    /**
     * Log a tenant lifecycle event.
     */
    public function logTenantAction(string $actorId, string $action, string $tenantId, array $changes = []): AuditLog
    {
        return $this->log($actorId, $action, $tenantId, [
            'entity_type' => 'tenant',
            'entity_id'   => $tenantId,
            'changes'     => $changes,
        ]);
    }

    /**
     * Log a payment mutation.
     */
    public function logPayment(string $actorId, string $tenantId, string $paymentId, string $action, array $data = []): AuditLog
    {
        return $this->log($actorId, $action, $tenantId, [
            'entity_type' => 'payment',
            'entity_id'   => $paymentId,
            'data'        => $data,
        ]);
    }

    /**
     * Log a user account state change.
     */
    public function logUserAction(string $actorId, string $action, string $userId, array $data = []): AuditLog
    {
        return $this->log($actorId, $action, null, [
            'entity_type' => 'user',
            'entity_id'   => $userId,
            'data'        => $data,
        ]);
    }

    /**
     * Feature 3 — Log a payment confirmation with a full immutable snapshot.
     * The snapshot is stored in the SuperAdmin audit trail for dispute resolution.
     * Even if the syndic's record is somehow altered, this remains untouched.
     */
    public function logPaymentSnapshot(string $actorId, string $tenantId, \App\Models\Payment $payment): AuditLog
    {
        $payment->loadMissing(['apartment.building', 'recorder', 'apartment.residents']);

        $snapshot = [
            'payment_id'     => $payment->id,
            'receipt_number' => $payment->receipt_number,
            'amount_paid'    => (float) $payment->amount_paid,
            'payment_method' => $payment->payment_method,
            'payment_date'   => $payment->payment_date?->format('Y-m-d'),
            'covered_months' => $payment->covered_months,
            'is_partial'     => $payment->is_partial,
            'remaining_balance' => (float) $payment->remaining_balance,
            'apartment'      => [
                'id'          => $payment->apartment?->id,
                'unit_number' => $payment->apartment?->unit_number,
                'building'    => $payment->apartment?->building?->name,
            ],
            'resident'       => $payment->apartment?->residents?->first()?->full_name,
            'recorder'       => $payment->recorder?->full_name,
            'confirmed_at'   => now()->toIso8601String(),
        ];

        return AuditLog::record(
            $actorId,
            'PAYMENT_CONFIRMED_SNAPSHOT',
            $tenantId,
            ['entity_type' => 'payment', 'entity_id' => $payment->id],
            $snapshot, // Feature 3 — immutable dispute record
        );
    }

    /**
     * Log an impersonation event.
     */
    public function logImpersonation(string $superAdminId, string $targetUserId, string $tenantId): AuditLog
    {
        return $this->log($superAdminId, 'IMPERSONATE_USER', $tenantId, [
            'entity_type'    => 'impersonation',
            'target_user_id' => $targetUserId,
            'warning'        => 'Super Admin impersonation session initiated',
        ]);
    }
}

