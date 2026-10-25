<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApartmentController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $tenantId = auth()->user()->tenant_id;

        $buildings = Building::where('tenant_id', $tenantId)->withCount('apartments')->get();
        $isComplex = $tenant && ($tenant->property_type === 'COMPLEX' || $buildings->count() > 1);

        $query = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with(['building', 'residents']);

        // Filters
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('search')) {
            $query->where('unit_number', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            if ($request->status === 'VACANT') {
                $query->whereDoesntHave('residents');
            } elseif ($request->status === 'OCCUPIED') {
                $query->whereHas('residents');
            }
        }

        if ($request->filled('billable')) {
            $query->where('is_billable', $request->billable === '1');
        }

        $apartments = $query->orderBy('building_id')
            ->orderByRaw('LENGTH(unit_number) ASC, unit_number ASC')
            ->paginate(25)
            ->withQueryString();

        $totalCount      = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->count();
        $occupiedCount   = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->has('residents')->count();
        $billableCount   = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->billable()->count();
        $expectedMonthly = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))->billable()->sum('monthly_fee');

        return view('portal.apartments.index', compact('apartments', 'buildings', 'isComplex', 'totalCount', 'occupiedCount', 'billableCount', 'expectedMonthly'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'building_id'   => ['required', 'exists:buildings,id'],
            'unit_number'   => ['required', 'string', 'max:50'],
            'monthly_fee'   => ['required', 'numeric', 'min:0'],
            'is_billable'   => ['boolean'],
        ]);

        // Ensure building belongs to tenant
        $building = Building::where('tenant_id', $tenantId)->findOrFail($validated['building_id']);

        $building->apartments()->create([
            'unit_number'  => $validated['unit_number'],
            'floor_number' => 0,
            'monthly_fee'  => $validated['monthly_fee'],
            'is_billable'  => $request->boolean('is_billable', true),
        ]);

        return back()->with('success', 'تم إضافة الشقة بنجاح.');
    }

    public function bulkGenerate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'building_id'   => ['required', 'exists:buildings,id'],
            'total_units'   => ['required', 'integer', 'min:1', 'max:500'],
            'unit_prefix'   => ['nullable', 'string', 'max:20'],
            'monthly_fee'   => ['required', 'numeric', 'min:0'],
        ]);

        $building = Building::where('tenant_id', $tenantId)->findOrFail($validated['building_id']);
        $prefix = trim($validated['unit_prefix'] ?? '');
        $existingCount = $building->apartments()->count();

        DB::transaction(function () use ($building, $validated, $prefix, $existingCount) {
            for ($i = 1; $i <= $validated['total_units']; $i++) {
                $num = $existingCount + $i;
                $unitNumber = $prefix !== '' ? "{$prefix}{$num}" : (string) $num;
                $building->apartments()->firstOrCreate(
                    ['unit_number' => $unitNumber],
                    [
                        'floor_number' => 0,
                        'monthly_fee'  => $validated['monthly_fee'],
                        'is_billable'  => true,
                    ]
                );
            }
        });

        return back()->with('success', "تم توليد {$validated['total_units']} شقة بنجاح بالعمارة {$building->name}.");
    }

    public function history($id)
    {
        $tenantId = auth()->user()->tenant_id;

        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with([
                'building',
                'residents',
                'payments' => fn ($q) => $q->where('status', '!=', 'DRAFT')->orderByDesc('payment_date'),
            ])
            ->findOrFail($id);

        $paymentsByYear = $apartment->payments->groupBy(function ($p) {
            return $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('Y') : 'غير محدد';
        });

        return response()->json([
            'apartment' => [
                'id' => $apartment->id,
                'unit_number' => $apartment->unit_number,
                'clean_unit' => str_starts_with(trim($apartment->unit_number), 'شقة') ? $apartment->unit_number : 'شقة ' . $apartment->unit_number,
                'building_name' => $apartment->building?->name ?? '—',
                'block' => $apartment->building?->block,
                'monthly_fee' => (float) $apartment->monthly_fee,
                'is_billable' => $apartment->is_billable,
                'residents' => $apartment->residents->map(fn ($r) => [
                    'name' => $r->full_name,
                    'phone' => $r->phone,
                    'email' => $r->email,
                    'type' => $r->pivot?->occupancy_type === 'OWNER' ? 'مالك' : 'مكترٍ',
                ]),
            ],
            'payments_by_year' => $paymentsByYear->map(fn ($group, $year) => [
                'year' => $year,
                'count' => $group->count(),
                'total_amount' => (float) $group->sum('amount_paid'),
                'payments' => $group->map(fn ($p) => [
                    'id' => $p->id,
                    'receipt_number' => $p->receipt_number ?? substr($p->id, 0, 8),
                    'amount_paid' => (float) $p->amount_paid,
                    'payment_date' => $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('Y/m/d') : '—',
                    'covered_months' => is_array($p->covered_months) ? $p->covered_months : [$p->covered_months],
                    'payment_method' => match($p->payment_method) {
                        'CASH' => '💵 نقداً',
                        'BANK_TRANSFER' => '🏦 تحويل بنكي',
                        'CHEQUE' => '📝 شيك بنكي',
                        default => $p->payment_method
                    },
                    'status' => $p->status,
                    'preview_url' => route('receipts.download', ['payment' => $p->id]),
                    'download_url' => route('receipts.download', ['payment' => $p->id, 'download' => 1]),
                ])->values(),
            ])->values(),
        ]);
    }


    public function vacate(Request $request, $id)
    {
        if ($error = $this->verifySensitivePassword($request, 'تفريغ الشقة وتصفير السجلات')) {
            return $error;
        }

        $tenantId = auth()->user()->tenant_id;

        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->with('residents')
            ->findOrFail($id);

        DB::transaction(function () use ($apartment) {
            // Detach all residents
            $apartment->residents()->detach();

            // Reset payments for this apartment
            $apartment->payments()->delete();

            // Reset created_at so arrears calculation restarts clean from now
            $apartment->forceFill([
                'created_at' => now(),
            ])->saveQuietly();
        });

        return back()->with('success', "تم تفريغ الشقة {$apartment->unit_number} وإعادة ضبط حساباتها لتصبح شاغرة.");
    }

    public function toggleBillable($id)
    {
        $tenantId = auth()->user()->tenant_id;

        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $apartment->update([
            'is_billable' => ! $apartment->is_billable,
        ]);

        $status = $apartment->is_billable ? 'خاضعة للأداء' : 'معفاة من السانديك';
        return back()->with('success', "تم تحديث حالة الشقة {$apartment->unit_number} إلى: {$status}");
    }

    public function destroy(Request $request, $id)
    {
        if ($error = $this->verifySensitivePassword($request, 'حذف الشقة نهائياً')) {
            return $error;
        }

        $tenantId = auth()->user()->tenant_id;

        $apartment = Apartment::whereHas('building', fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $unitNumber = $apartment->unit_number;

        DB::transaction(function () use ($apartment) {
            $apartment->residents()->detach();
            $apartment->payments()->delete();
            $apartment->delete();
        });

        return back()->with('success', "تم حذف الشقة {$unitNumber} بنجاح.");
    }
}
