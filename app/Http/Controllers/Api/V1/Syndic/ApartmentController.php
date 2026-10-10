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
                $q->with([
                    'residents',
                    'payments' => fn ($p) => $p->orderByDesc('payment_date'),
                ])
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
                'apartments'   => $building->apartments->map(function ($apt) use ($building) {
                    $resident = $apt->residents->first();
                    $totalPaid = (float) $apt->payments->sum('amount_paid');
                    
                    $monthsActive = max(1, $apt->created_at->diffInMonths(now()) + 1);
                    $totalDue = $apt->monthly_fee * $monthsActive;
                    $arrears = max(0, round($totalDue - $totalPaid, 2));

                    return [
                        'id'             => $apt->id,
                        'building_id'    => $building->id,
                        'building_name'  => $building->name,
                        'unit_number'    => $apt->unit_number,
                        'floor'          => $apt->floor,
                        'monthly_fee'    => (float) $apt->monthly_fee,
                        'total_paid'     => $totalPaid,
                        'arrears'        => $arrears,
                        'is_settled'     => $arrears <= 0,
                        'resident'       => $resident ? [
                            'id'             => $resident->id,
                            'name'           => $resident->full_name,
                            'email'          => $resident->email,
                            'phone'          => $resident->phone,
                            'occupancy_type' => $resident->pivot->occupancy_type ?? 'OWNER',
                        ] : null,
                        'last_payment'   => $apt->payments->first()?->payment_date?->format('Y-m-d'),
                        'payments'       => $apt->payments->map(fn ($p) => [
                            'id'             => $p->id,
                            'receipt_number' => $p->receipt_number,
                            'amount_paid'    => (float) $p->amount_paid,
                            'payment_method' => $p->payment_method,
                            'payment_date'   => $p->payment_date?->format('Y-m-d'),
                            'covered_months' => $p->covered_months,
                            'receipt_url'    => $p->receipt_url,
                        ])->values(),
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
