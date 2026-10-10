<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSyndicRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterSyndicController extends Controller
{
    /**
     * POST /api/v1/auth/register-syndic
     *
     * Creates a Syndic organization in PENDING_APPROVAL state.
     * No invitation code required. Super Admin must approve.
     */
    public function __invoke(RegisterSyndicRequest $request)
    {
        $result = DB::transaction(function () use ($request) {
            // Create tenant (organization) in pending state
            $tenant = Tenant::create([
                'name'            => $request->organization_name,
                'property_type'   => $request->property_type,
                'plan_type'       => 'TRIAL',
                'status'          => 'PENDING_APPROVAL',
                'invitation_code' => Tenant::generateInvitationCode(),
                'trial_ends_at'   => now()->addDays(30),
            ]);

            // Create Syndic admin user
            $user = User::create([
                'tenant_id'  => $tenant->id,
                'role'       => 'ADMIN_SYNDIC',
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'locale'     => $request->locale ?? 'ar',
                'status'     => 'PENDING',
            ]);

            return compact('tenant', 'user');
        });

        return response()->json([
            'message' => __('auth.syndic_registration_pending'),
            'tenant'  => [
                'id'     => $result['tenant']->id,
                'name'   => $result['tenant']->name,
                'status' => $result['tenant']->status,
            ],
        ], 201);
    }
}
