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
