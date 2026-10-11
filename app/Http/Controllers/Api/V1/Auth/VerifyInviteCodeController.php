<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyInviteCodeRequest;
use App\Models\Tenant;

class VerifyInviteCodeController extends Controller
{
    /**
     * POST /api/v1/auth/verify-invite-code
     *
     * Validates an invitation code and returns the condominium metadata.
     * Rate limited to 10 requests/minute per IP (§1.2).
     */
    public function __invoke(VerifyInviteCodeRequest $request)
    {
        $code = $request->invitation_code ?? $request->invite_code;
        $tenant = Tenant::where('invitation_code', $code)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $tenant) {
            return response()->json([
                'valid'   => false,
                'message' => __('auth.invalid_invite_code'),
            ], 422);
        }

        // Return condominium topology for dynamic form rendering
        $buildings = $tenant->buildings()
            ->with(['apartments' => fn ($q) => $q->with(['residents' => fn ($rq) => $rq->where('users.status', 'ACTIVE')])->orderBy('floor')->orderBy('unit_number')])
            ->get()
            ->map(fn ($building) => [
                'id'           => $building->id,
                'block'        => $building->block,
                'name'         => $building->name,
                'total_floors' => $building->total_floors,
                'apartments'   => $building->apartments->map(fn ($apt) => [
                    'id'          => $apt->id,
                    'unit_number' => $apt->unit_number,
                    'floor'       => $apt->floor,
                    'monthly_fee' => (float) $apt->monthly_fee,
                    'is_occupied' => $apt->residents->isNotEmpty(),
                ]),
            ]);

        return response()->json([
            'valid'           => true,
            'tenant_name'     => $tenant->name,
            'is_complex'      => $tenant->property_type === 'MULTI_BUILDING_COMPLEX',
            'buildings'       => $buildings,
        ]);
    }
}
