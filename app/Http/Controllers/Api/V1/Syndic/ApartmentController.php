<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    /**
     * GET /api/v1/syndic/apartments
     *
     * Lists all buildings → apartments with occupancy and payment status.
     */
    public function index(Request $request): JsonResponse
    {
        $buildings = Building::where('tenant_id', $request->user()->tenant_id)
            ->with(['apartments' => function ($q) {
                $q->with(['residents', 'payments' => fn ($p) => $p->latest('payment_date')->limit(1)])
                  ->orderBy('floor')
                  ->orderBy('unit_number');
            }])
            ->orderBy('name')
            ->get();

        $result = $buildings->map(function ($building) {
            return [
                'id'           => $building->id,
                'name'         => $building->name,
                'total_floors' => $building->total_floors,
                'apartments'   => $building->apartments->map(function ($apt) {
                    $resident = $apt->residents->first();
                    return [
                        'id'           => $apt->id,
                        'unit_number'  => $apt->unit_number,
                        'floor'        => $apt->floor,
                        'monthly_fee'  => $apt->monthly_fee,
                        'resident'     => $resident ? [
                            'id'             => $resident->id,
                            'name'           => $resident->full_name,
                            'phone'          => $resident->phone,
                            'occupancy_type' => $resident->pivot->occupancy_type,
                        ] : null,
                        'last_payment' => $apt->payments->first()?->payment_date?->format('Y-m-d'),
                    ];
                }),
            ];
        });

        return response()->json(['buildings' => $result]);
    }

    /**
     * POST /api/v1/syndic/apartments
     *
     * Create or update apartment units.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'building_id'  => ['required', 'uuid', 'exists:buildings,id'],
            'unit_number'  => ['required', 'string', 'max:50'],
            'floor'        => ['required', 'integer', 'min:0'],
            'monthly_fee'  => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        // Verify building belongs to tenant
        $building = Building::where('tenant_id', $request->user()->tenant_id)
            ->where('id', $request->building_id)
            ->firstOrFail();

        $apartment = Apartment::updateOrCreate(
            [
                'building_id'  => $building->id,
                'unit_number'  => $request->unit_number,
            ],
            [
                'floor'       => $request->floor,
                'monthly_fee' => $request->monthly_fee,
            ]
        );

        return response()->json([
            'message'   => __('apartments.saved'),
            'apartment' => $apartment,
        ], 201);
    }
}
