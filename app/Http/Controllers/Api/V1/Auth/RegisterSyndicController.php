<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterSyndicRequest;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterSyndicController extends Controller
{
    /**
     * POST /api/v1/auth/register-syndic
     *
     * Creates a Syndic organization in PENDING_APPROVAL state,
     * along with its buildings and initial apartments.
     */
    public function __invoke(RegisterSyndicRequest $request)
    {
        $result = DB::transaction(function () use ($request) {
            // Create tenant (organization) in PENDING_APPROVAL state awaiting SuperAdmin approval
            $tenant = Tenant::create([
                'name'                      => $request->organization_name,
                'city'                      => $request->city,
                'property_type'             => $request->property_type,
                'plan_type'                 => 'TRIAL',
                'status'                    => 'PENDING_APPROVAL',
                'invitation_code'           => Tenant::generateInvitationCode(),
                'trial_ends_at'             => now()->addDays(30),
                'default_monthly_fee'       => (float) ($request->default_monthly_fee ?? 200.0),
                'bank_rib'                  => $request->bank_rib,
                // Feature 9 — Opening treasury & bank balance (set at registration, locked after)
                'initial_treasury_balance'  => (float) ($request->initial_treasury_balance ?? 0),
                'initial_bank_balance'      => (float) ($request->initial_bank_balance ?? 0),
                'initial_balance_locked'    => $request->has('initial_treasury_balance') || $request->has('initial_bank_balance'),
            ]);

            // Create Syndic admin user in PENDING state
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

            $fee = (float) ($request->default_monthly_fee ?? 200.0);

            // 1. Single Building Scenario (Requirement 1 & 5)
            if ($request->property_type === 'SINGLE_BUILDING') {
                $floors = (int) ($request->total_floors ?? 1);
                $unitsPerFloor = (int) ($request->units_per_floor ?? 0);
                $totalUnits = (int) ($request->total_units ?? ($floors * ($unitsPerFloor ?: 1)));

                $building = Building::create([
                    'tenant_id'    => $tenant->id,
                    'name'         => $request->organization_name,
                    'total_floors' => max(1, $floors),
                ]);

                $scheme = $request->numbering_scheme ?? 'sequential';
                if ($unitsPerFloor > 0 && $floors > 0) {
                    $unitCounter = 1;
                    for ($f = 1; $f <= $floors; $f++) {
                        for ($u = 1; $u <= $unitsPerFloor; $u++) {
                            if ($scheme === 'floor_based') {
                                $uName = 'شقة ' . (($f * 100) + $u);
                            } elseif ($scheme === 'floor_unit') {
                                $uName = "شقة {$f}-{$u}";
                            } else {
                                $uName = "شقة $unitCounter";
                            }
                            Apartment::create([
                                'building_id' => $building->id,
                                'unit_number' => $uName,
                                'floor'       => $f,
                                'monthly_fee' => $fee,
                            ]);
                            $unitCounter++;
                        }
                    }
                } elseif ($totalUnits > 0) {
                    for ($i = 1; $i <= $totalUnits; $i++) {
                        $floorNum = $floors > 0 ? (int) ceil($i / max(1, ceil($totalUnits / max(1, $floors)))) : 1;
                        Apartment::create([
                            'building_id' => $building->id,
                            'unit_number' => "شقة $i",
                            'floor'       => max(1, $floorNum),
                            'monthly_fee' => $fee,
                        ]);
                    }
                }
            }

            // 2. Multi-Building Complex Scenario (Requirement 1 & 5)
            if ($request->property_type === 'MULTI_BUILDING_COMPLEX' && is_array($request->buildings)) {
                foreach ($request->buildings as $bData) {
                    $bFloors = (int) ($bData['total_floors'] ?? 1);
                    $bUnitsPerFloor = (int) ($bData['units_per_floor'] ?? 0);
                    $bTotalUnits = (int) ($bData['total_units'] ?? ($bFloors * ($bUnitsPerFloor ?: 1)));
                    $bFee = (float) ($bData['monthly_fee'] ?? $fee);
                    $bScheme = $bData['numbering_scheme'] ?? $request->numbering_scheme ?? 'sequential';

                    $building = Building::create([
                        'tenant_id'    => $tenant->id,
                        'block'        => $bData['block'] ?? null,
                        'name'         => $bData['name'] ?? ('عمارة ' . ($bData['block'] ?? '1')),
                        'total_floors' => max(1, $bFloors),
                    ]);

                    if ($bUnitsPerFloor > 0 && $bFloors > 0) {
                        $unitCounter = 1;
                        for ($f = 1; $f <= $bFloors; $f++) {
                            for ($u = 1; $u <= $bUnitsPerFloor; $u++) {
                                if ($bScheme === 'floor_based') {
                                    $uName = 'شقة ' . (($f * 100) + $u);
                                } elseif ($bScheme === 'floor_unit') {
                                    $uName = "شقة {$f}-{$u}";
                                } else {
                                    $uName = "شقة $unitCounter";
                                }
                                Apartment::create([
                                    'building_id' => $building->id,
                                    'unit_number' => $uName,
                                    'floor'       => $f,
                                    'monthly_fee' => $bFee,
                                ]);
                                $unitCounter++;
                            }
                        }
                    } elseif ($bTotalUnits > 0) {
                        for ($i = 1; $i <= $bTotalUnits; $i++) {
                            $floorNum = $bFloors > 0 ? (int) ceil($i / max(1, ceil($bTotalUnits / max(1, $bFloors)))) : 1;
                            Apartment::create([
                                'building_id' => $building->id,
                                'unit_number' => "شقة $i",
                                'floor'       => max(1, $floorNum),
                                'monthly_fee' => $bFee,
                            ]);
                        }
                    }
                }
            }

            return compact('tenant', 'user');
        });

        return response()->json([
            'message'      => 'تم تسجيل طلب الإقامة وحساب السانديك بنجاح، وهو الآن في انتظار مراجعة وتفعيل السوبر أدمن.',
            // Feature 14 — Contact info for the pending syndic to reach the admin
            'contact_info' => [
                'email'    => config('smartsyndic.admin_contact.email', 'support@smartsyndic.com'),
                'phone'    => config('smartsyndic.admin_contact.phone', '+212 600 000 000'),
                'whatsapp' => config('smartsyndic.admin_contact.whatsapp', '+212 600 000 000'),
            ],
            'tenant'  => [
                'id'              => $result['tenant']->id,
                'name'            => $result['tenant']->name,
                'status'          => $result['tenant']->status,
                'invitation_code' => $result['tenant']->invitation_code,
            ],
        ], 201);
    }
}
