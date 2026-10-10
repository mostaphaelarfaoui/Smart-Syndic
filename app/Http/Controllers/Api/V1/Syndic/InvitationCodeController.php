<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationCodeController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * POST /api/v1/syndic/invitation-code/regenerate
     *
     * Invalidates previous code and creates a new one.
     */
    public function regenerate(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $oldCode = $tenant->invitation_code;

        $tenant->regenerateInvitationCode();

        $this->auditService->logTenantAction(
            $request->user()->id,
            'INVITATION_CODE_REGENERATED',
            $tenant->id,
            ['old_code' => $oldCode, 'new_code' => $tenant->invitation_code],
        );

        return response()->json([
            'message'         => __('tenant.code_regenerated'),
            'invitation_code' => $tenant->invitation_code,
        ]);
    }
}
