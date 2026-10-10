<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterResidentRequest;
use App\Events\ResidentRegistered;
use App\Models\ResidentApartment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterResidentController extends Controller
{
    /**
     * POST /api/v1/auth/register-resident
     *
     * Creates a resident account in PENDING state.
     * Requires a valid, previously verified invitation code.
     */
    public function __invoke(RegisterResidentRequest $request)
    {
        $tenant = Tenant::where('invitation_code', $request->invitation_code)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        $user = DB::transaction(function () use ($request, $tenant) {
            // Create user in PENDING state
            $user = User::create([
                'tenant_id'  => $tenant->id,
                'role'       => 'RESIDENT',
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'locale'     => $request->locale ?? 'ar',
                'status'     => 'PENDING',
            ]);

            // Link user to apartment
            ResidentApartment::create([
                'user_id'            => $user->id,
                'apartment_id'       => $request->apartment_id,
                'occupancy_type'     => $request->occupancy_type,
                'is_primary_contact' => true,
            ]);

            return $user;
        });

        // Notify Syndic of new registration
        event(new ResidentRegistered($user));

        return response()->json([
            'message' => __('auth.registration_pending'),
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->full_name,
                'email' => $user->email,
                'status' => $user->status,
            ],
        ], 201);
    }
}
