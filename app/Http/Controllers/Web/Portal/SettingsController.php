<?php

namespace App\Http\Controllers\Web\Portal;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    public function index()
    {
        $tenant = auth()->user()->tenant;
        return view('portal.settings.index', compact('tenant'));
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'property_type'            => ['required', 'string'],
            'bank_rib'                 => ['nullable', 'string', 'max:50'],
            'is_transparency_enabled'  => ['boolean'],
            'initial_treasury_balance' => ['nullable', 'numeric'],
            'initial_bank_balance'     => ['nullable', 'numeric'],
        ]);

        $updateData = [
            'name'                    => $validated['name'],
            'property_type'           => $validated['property_type'],
            'bank_rib'                => $validated['bank_rib'] ?? null,
            'is_transparency_enabled' => $request->boolean('is_transparency_enabled', true),
        ];

        // Only update initial balances if not locked yet
        if (! $tenant->initial_balance_locked) {
            if ($request->filled('initial_treasury_balance')) {
                $updateData['initial_treasury_balance'] = $validated['initial_treasury_balance'];
            }
            if ($request->filled('initial_bank_balance')) {
                $updateData['initial_bank_balance'] = $validated['initial_bank_balance'];
            }
        }

        $tenant->update($updateData);

        return back()->with('success', 'تم حفظ إعدادات الإقامة بنجاح.');
    }

    public function uploadStamp(Request $request)
    {
        $request->validate([
            'stamp' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $tenant = auth()->user()->tenant;

        if ($tenant->stamp_image_path) {
            $this->uploadService->delete($tenant->stamp_image_path);
        }

        $path = $this->uploadService->upload($request->file('stamp'), 'stamps');
        $tenant->update(['stamp_image_path' => $path]);

        return back()->with('success', 'تم رفع وتعيين خاتم السانديك بنجاح. سيظهر تلقائياً على كل التوصيلات PDF.');
    }

    public function deleteStamp()
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->stamp_image_path) {
            $this->uploadService->delete($tenant->stamp_image_path);
            $tenant->update(['stamp_image_path' => null]);
        }

        return back()->with('success', 'تم حذف خاتم السانديك.');
    }
}
