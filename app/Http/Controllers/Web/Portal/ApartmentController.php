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
        $tenantId = auth()->user()->tenant_id;

        $buildings = Building::where('tenant_id', $tenantId)->get();

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

        $apartments = $query->orderBy('unit_number')->paginate(20)->withQueryString();

        return view('portal.apartments.index', compact('apartments', 'buildings'));
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'building_id'   => ['required', 'exists:buildings,id'],
            'unit_number'   => ['required', 'string', 'max:50'],
            'floor_number'  => ['nullable', 'integer'],
            'monthly_fee'   => ['required', 'numeric', 'min:0'],
            'is_billable'   => ['boolean'],
        ]);

        // Ensure building belongs to tenant
        $building = Building::where('tenant_id', $tenantId)->findOrFail($validated['building_id']);

        $building->apartments()->create([
            'unit_number'  => $validated['unit_number'],
            'floor_number' => $validated['floor_number'] ?? 0,
            'monthly_fee'  => $validated['monthly_fee'],
            'is_billable'  => $request->boolean('is_billable', true),
        ]);

        return back()->with('success', 'تم إضافة الشقة بنجاح.');
    }

    public function bulkGenerate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'building_id'    => ['required', 'exists:buildings,id'],
            'floors_count'   => ['required', 'integer', 'min:1', 'max:30'],
            'units_per_floor'=> ['required', 'integer', 'min:1', 'max:20'],
            'monthly_fee'    => ['required', 'numeric', 'min:0'],
        ]);

        $building = Building::where('tenant_id', $tenantId)->findOrFail($validated['building_id']);

        DB::transaction(function () use ($building, $validated) {
            $unitCounter = 1;
            for ($floor = 1; $floor <= $validated['floors_count']; $floor++) {
                for ($u = 1; $u <= $validated['units_per_floor']; $u++) {
                    $unitNumber = (string) $unitCounter;
                    $building->apartments()->firstOrCreate(
                        ['unit_number' => $unitNumber],
                        [
                            'floor_number' => $floor,
                            'monthly_fee'  => $validated['monthly_fee'],
                            'is_billable'  => true,
                        ]
                    );
                    $unitCounter++;
                }
            }
        });

        return back()->with('success', 'تم توليد الشقق بنجاح.');
    }

    public function vacate($id)
    {
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

    public function destroy($id)
    {
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
