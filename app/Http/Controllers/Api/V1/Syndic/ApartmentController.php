<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Building;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApartmentController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

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
                    'residents' => fn ($r) => $r->where('users.status', 'ACTIVE'),
                    'payments' => fn ($p) => $p->orderByDesc('payment_date'),
                ])
                ->orderBy('floor')
                ->orderBy('unit_number');
            }])
            ->orderBy('block')
            ->orderBy('name')
            ->get();

        $result = $buildings->map(function ($building) {
            return [
                'id'           => $building->id,
                'block'        => $building->block,
                'name'         => $building->name,
                'total_floors' => $building->total_floors,
                'apartments'   => $building->apartments->map(function ($apt) use ($building) {
                    $resident = $apt->residents->first();
                    $confirmedPayments = $apt->payments->filter(fn ($p) => $p->status !== 'DRAFT');
                    $totalPaid = (float) $confirmedPayments->sum('amount_paid');
                    
                    if (! $apt->is_billable) {
                        $arrears = 0.0;
                        $isSettled = true;
                    } else {
                        $monthsActive = max(1, $apt->created_at->diffInMonths(now()) + 1);
                        $totalDue = $apt->monthly_fee * $monthsActive;
                        $arrears = max(0, round($totalDue - $totalPaid, 2));
                        $isSettled = $arrears <= 0;
                    }

                    return [
                        'id'             => $apt->id,
                        'building_id'    => $building->id,
                        'building_name'  => $building->name,
                        'block'          => $building->block,
                        'unit_number'    => $apt->unit_number,
                        'floor'          => $apt->floor,
                        'monthly_fee'    => (float) $apt->monthly_fee,
                        'is_billable'    => (bool) $apt->is_billable,
                        'total_paid'     => $totalPaid,
                        'arrears'        => $arrears,
                        'is_settled'     => $isSettled,
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
     * Create or update a single apartment unit.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'building_id'  => ['nullable', 'uuid', 'exists:buildings,id'],
            'building_name'=> ['nullable', 'string', 'max:100'],
            'block'        => ['nullable', 'string', 'max:100'],
            'unit_number'  => ['required', 'string', 'max:50'],
            'floor'        => ['nullable', 'integer', 'min:0'],
            'monthly_fee'  => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $building = null;
        $floor = (int) ($request->floor ?? 0);

        if ($request->filled('building_id')) {
            $building = Building::where('tenant_id', $tenantId)
                ->where('id', $request->building_id)
                ->firstOrFail();
        } elseif ($request->filled('building_name')) {
            $building = Building::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name'      => $request->building_name,
                    'block'     => $request->block,
                ],
                [
                    'total_floors' => max(1, $floor),
                ]
            );
        } else {
            $building = Building::where('tenant_id', $tenantId)->first();
            if (!$building) {
                $building = Building::create([
                    'tenant_id'    => $tenantId,
                    'name'         => $request->user()->tenant->name ?? 'العمارة الرئيسية',
                    'total_floors' => max(1, $floor),
                ]);
            }
        }

        $apartment = Apartment::updateOrCreate(
            [
                'building_id'  => $building->id,
                'unit_number'  => $request->unit_number,
            ],
            [
                'floor'       => $floor,
                'monthly_fee' => $request->monthly_fee,
            ]
        );

        return response()->json([
            'message'   => __('apartments.saved'),
            'apartment' => $apartment,
        ], 201);
    }

    /**
     * POST /api/v1/syndic/apartments/bulk-generate
     *
     * Generates apartment units in bulk by floors or total units (Requirements 2 & 5).
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'building_id'      => ['nullable', 'uuid', 'exists:buildings,id'],
            'building_name'    => ['nullable', 'string', 'max:100'],
            'block'            => ['nullable', 'string', 'max:100'],
            'total_floors'     => ['nullable', 'integer', 'min:1', 'max:50'],
            'units_per_floor'  => ['nullable', 'integer', 'min:1', 'max:20'],
            'total_units'      => ['nullable', 'integer', 'min:1', 'max:500'],
            'monthly_fee'      => ['required', 'numeric', 'min:0'],
            'unit_prefix'      => ['nullable', 'string', 'max:20'],
            'numbering_scheme' => ['nullable', 'string', 'in:sequential,floor_based,floor_unit'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $building = null;

        if ($request->filled('building_id')) {
            $building = Building::where('tenant_id', $tenantId)->findOrFail($request->building_id);
            if ($request->filled('block') && empty($building->block)) {
                $building->update(['block' => $request->block]);
            }
        } elseif ($request->filled('building_name')) {
            $building = Building::create([
                'tenant_id'    => $tenantId,
                'block'        => $request->block,
                'name'         => $request->building_name,
                'total_floors' => max(1, (int) ($request->total_floors ?? 1)),
            ]);
        } else {
            $building = Building::where('tenant_id', $tenantId)->first();
            if (!$building) {
                $building = Building::create([
                    'tenant_id'    => $tenantId,
                    'name'         => $request->user()->tenant->name ?? 'العمارة الرئيسية',
                    'total_floors' => max(1, (int) ($request->total_floors ?? 1)),
                ]);
            }
        }

        $floors = max(1, (int) ($request->total_floors ?? $building->total_floors ?? 1));
        $unitsPerFloor = (int) ($request->units_per_floor ?? 0);
        $totalUnits = (int) ($request->total_units ?? ($floors * ($unitsPerFloor ?: 1)));
        $fee = (float) $request->monthly_fee;
        $prefix = $request->has('unit_prefix') ? ($request->unit_prefix ?? '') : '';
        $numberingScheme = $request->numbering_scheme ?? 'sequential';

        $existingCount = $building->apartments()->count();
        $createdApartments = [];

        if ($unitsPerFloor > 0) {
            $unitCounter = $existingCount + 1;
            for ($f = 1; $f <= $floors; $f++) {
                for ($u = 1; $u <= $unitsPerFloor; $u++) {
                    if ($numberingScheme === 'floor_based') {
                        $num = ($f * 100) + $u;
                        $unitNumber = $prefix ? "{$prefix}{$num}" : (string) $num;
                    } elseif ($numberingScheme === 'floor_unit') {
                        $num = "{$f}-{$u}";
                        $unitNumber = $prefix ? "{$prefix}{$num}" : $num;
                    } else {
                        $unitNumber = $prefix ? "{$prefix}{$unitCounter}" : (string) $unitCounter;
                    }

                    $apt = Apartment::firstOrCreate(
                        [
                            'building_id' => $building->id,
                            'unit_number' => $unitNumber,
                        ],
                        [
                            'floor'       => $f,
                            'monthly_fee' => $fee,
                        ]
                    );
                    $createdApartments[] = $apt;
                    $unitCounter++;
                }
            }
        } else {
            for ($i = 1; $i <= $totalUnits; $i++) {
                $unitNum = $existingCount + $i;
                $floorNum = (int) ceil($unitNum / max(1, ceil($totalUnits / $floors)));
                $unitNumber = $prefix ? "{$prefix}{$unitNum}" : (string) $unitNum;
                $apt = Apartment::firstOrCreate(
                    [
                        'building_id' => $building->id,
                        'unit_number' => $unitNumber,
                    ],
                    [
                        'floor'       => max(1, $floorNum),
                        'monthly_fee' => $fee,
                    ]
                );
                $createdApartments[] = $apt;
            }
        }

        return response()->json([
            'message'         => 'تم توليد الشقق بنجاح دفعة واحدة',
            'building'        => [
                'id'           => $building->id,
                'block'        => $building->block,
                'name'         => $building->name,
                'total_floors' => $building->total_floors,
            ],
            'generated_count' => count($createdApartments),
        ], 201);
    }

    /**
     * PUT /api/v1/syndic/apartments/{id}
     *
     * Edit apartment unit number, floor, or monthly fee (Requirement 2).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'unit_number' => ['required', 'string', 'max:50'],
            'floor'       => ['required', 'integer', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'is_billable' => ['nullable', 'boolean'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $updateData = [
            'unit_number' => $request->unit_number,
            'floor'       => $request->floor,
            'monthly_fee' => $request->monthly_fee,
        ];

        if ($request->has('is_billable')) {
            $updateData['is_billable'] = $request->boolean('is_billable');
        }

        $apartment->update($updateData);

        return response()->json([
            'message'   => 'تم تحديث بيانات الشقة بنجاح',
            'apartment' => $apartment,
        ]);
    }

    /**
     * DELETE /api/v1/syndic/apartments/{id}
     *
     * Delete an apartment (Requirement 2).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['residents', 'payments'])
            ->findOrFail($id);

        $unitNumber = $apartment->unit_number;
        $residentNames = $apartment->residents->map(fn ($r) => $r->full_name)->implode(', ');

        DB::transaction(function () use ($apartment) {
            $apartment->delete(); // Triggers Apartment::deleting hook: deletes resident accounts & payments
        });

        $this->auditService->log(
            $request->user()->id,
            'APARTMENT_DELETED_WITH_RESIDENTS',
            $tenantId,
            [
                'unit_number'        => $unitNumber,
                'deleted_residents'  => $residentNames ?: 'لا يوجد ساكن',
            ]
        );

        return response()->json([
            'message' => "تم حذف الشقة {$unitNumber} بنجاح وحذف الحسابات المرتبطة بها.",
        ]);
    }

    /**
     * POST /api/v1/syndic/apartments/{id}/vacate
     *
     * Vacate an apartment: delete resident account, clear payments, reset unit so new resident can register.
     */
    public function vacate(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['residents'])
            ->findOrFail($id);

        $unitNumber = $apartment->unit_number;
        $residentNames = $apartment->residents->map(fn ($r) => $r->full_name)->implode(', ');

        DB::transaction(function () use ($apartment) {
            $residents = $apartment->residents()->get();
            foreach ($residents as $resident) {
                $resident->delete(); // Triggers User::deleting hook: clears payments & sets created_at = now()
            }
            $apartment->refresh();
        });

        $this->auditService->log(
            $request->user()->id,
            'APARTMENT_VACATED_AND_RESET',
            $tenantId,
            [
                'apartment_id'       => $apartment->id,
                'unit_number'        => $unitNumber,
                'vacated_residents'  => $residentNames,
            ]
        );

        return response()->json([
            'message'      => "تم إخلاء الشقة {$unitNumber} بنجاح وحذف حساب الساكن وتصفير المبالغ لتبدأ من جديد.",
            'apartment_id' => $apartment->id,
        ]);
    }

    /**
     * POST /api/v1/syndic/apartments/{id}/toggle-billable
     *
     * Exemption toggle:
     * is_billable = false → Exempt from syndic dues (0 arrears, excluded from expected income).
     * is_billable = true  → Included in billing and dues.
     */
    public function toggleBillable(Request $request, string $id): JsonResponse
    {
        $tenantId  = $request->user()->tenant_id;
        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $apartment->update(['is_billable' => ! $apartment->is_billable]);

        $statusAr = $apartment->is_billable ? 'إلغاء الإعفاء وإعادة إدراج الشقة في الواجبات' : 'إعفاء الشقة من واجبات السانديك';

        $this->auditService->log(
            $request->user()->id,
            $apartment->is_billable ? 'APARTMENT_EXEMPTION_CANCELLED' : 'APARTMENT_EXEMPTED',
            $tenantId,
            [
                'apartment_id' => $apartment->id,
                'unit_number'  => $apartment->unit_number,
                'is_billable'  => (bool) $apartment->is_billable,
            ]
        );

        return response()->json([
            'message'      => "تم بنجاح {$statusAr}.",
            'apartment_id' => $apartment->id,
            'is_billable'  => (bool) $apartment->is_billable,
        ]);
    }
}
