<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Events\ResidentApproved;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingResidentController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * GET /api/v1/syndic/pending-residents
     */
    public function index(Request $request): JsonResponse
    {
        $residents = User::where('tenant_id', $request->user()->tenant_id)
            ->where('role', 'RESIDENT')
            ->where('status', 'PENDING')
            ->with(['apartments.building'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data'              => $residents,
            'pending_residents' => $residents,
        ]);
    }

    /**
     * POST /api/v1/syndic/pending-residents/{id}/approve
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $resident = User::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $id)
            ->where('status', 'PENDING')
            ->firstOrFail();

        // Safety check: verify apartment is not already occupied by another active resident
        $apartment = $resident->apartments()->first();
        if ($apartment && $apartment->residents()->where('users.status', 'ACTIVE')->where('users.id', '!=', $resident->id)->exists()) {
            return response()->json([
                'message' => 'تعذر قبول الطلب: هذه الشقة مشغولة ومسجل بها ساكن معتمد بالفعل.',
            ], 422);
        }

        $resident->update(['status' => 'ACTIVE']);

        $this->auditService->logUserAction(
            $request->user()->id,
            'RESIDENT_APPROVED',
            $resident->id,
        );

        event(new ResidentApproved($resident));

        return response()->json([
            'message' => __('residents.approved'),
            'resident' => $resident->only(['id', 'first_name', 'last_name', 'email', 'status']),
        ]);
    }

    /**
     * POST /api/v1/syndic/pending-residents/{id}/reject
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $resident = User::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $id)
            ->where('status', 'PENDING')
            ->firstOrFail();

        $this->auditService->logUserAction(
            $request->user()->id,
            'RESIDENT_REJECTED',
            $resident->id,
        );

        // Remove apartment links and delete user
        $resident->residentApartments()->delete();
        $resident->delete();

        return response()->json(['message' => __('residents.rejected')]);
    }
}
