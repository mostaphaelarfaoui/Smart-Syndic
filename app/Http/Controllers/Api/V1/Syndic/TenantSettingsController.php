<?php

namespace App\Http\Controllers\Api\V1\Syndic;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSettingsController extends Controller
{
    public function __construct(
        private readonly FileUploadService $uploadService,
    ) {}

    /**
     * GET /api/v1/syndic/settings
     *
     * Returns tenant settings including stamp URL (Feature 1), treasury balance (Feature 9).
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        return response()->json([
            'tenant' => [
                'id'                        => $tenant->id,
                'name'                      => $tenant->name,
                'city'                      => $tenant->city,
                'property_type'             => $tenant->property_type,
                'bank_rib'                  => $tenant->bank_rib,
                'default_monthly_fee'       => (float) ($tenant->default_monthly_fee ?? 200.0),
                'is_transparency_enabled'   => (bool) $tenant->is_transparency_enabled,
                'stamp_url'                 => $tenant->stamp_url, // Feature 1
                'has_stamp'                 => ! empty($tenant->stamp_image_path),
                'initial_treasury_balance'  => (float) $tenant->initial_treasury_balance, // Feature 9
                'initial_bank_balance'      => (float) $tenant->initial_bank_balance,     // Feature 9
                'initial_balance_locked'    => (bool) $tenant->initial_balance_locked,    // Feature 9
                'invitation_code'           => $tenant->invitation_code,
                'total_apartments'          => $tenant->apartments()->count(),
                'contact_info'              => $tenant->contact_info,
            ],
        ]);
    }

    /**
     * POST /api/v1/syndic/settings/stamp
     *
     * Feature 1 — Upload/replace the syndic stamp image.
     * This stamp will appear on all generated PDF receipts.
     */
    public function uploadStamp(Request $request): JsonResponse
    {
        $request->validate([
            'stamp' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $tenant = $request->user()->tenant;

        // Delete old stamp if exists
        if ($tenant->stamp_image_path) {
            $this->uploadService->delete($tenant->stamp_image_path);
        }

        $path = $this->uploadService->upload($request->file('stamp'), 'stamps');
        $tenant->update(['stamp_image_path' => $path]);

        return response()->json([
            'message'   => 'تم رفع صورة الطابع بنجاح.',
            'stamp_url' => $tenant->fresh()->stamp_url,
        ]);
    }

    /**
     * DELETE /api/v1/syndic/settings/stamp
     *
     * Feature 1 — Remove the stamp image.
     */
    public function deleteStamp(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if ($tenant->stamp_image_path) {
            $this->uploadService->delete($tenant->stamp_image_path);
            $tenant->update(['stamp_image_path' => null]);
        }

        return response()->json(['message' => 'تم حذف صورة الطابع.']);
    }

    /**
     * PUT /api/v1/syndic/settings
     *
     * Update general tenant settings (bank RIB, default monthly fee, transparency toggle, name, etc.).
     * Initial treasury balance can only be set if not yet locked (Feature 9).
     */
    public function update(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $rules = [
            'name'                         => ['sometimes', 'string', 'min:2', 'max:255'],
            'city'                         => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_rib'                     => ['sometimes', 'nullable', 'string', 'max:34'],
            'default_monthly_fee'          => ['sometimes', 'numeric', 'min:0'],
            'apply_to_existing_apartments' => ['sometimes', 'boolean'],
            'is_transparency_enabled'      => ['sometimes', 'boolean'],
            'contact_phone'                => ['sometimes', 'nullable', 'string'],
            'contact_email'                => ['sometimes', 'nullable', 'email'],
        ];

        // Feature 9 — Only allow setting initial balances if not yet locked
        if (! $tenant->initial_balance_locked) {
            $rules['initial_treasury_balance'] = ['sometimes', 'numeric', 'min:0'];
            $rules['initial_bank_balance']     = ['sometimes', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        $updateData = $request->only(['name', 'city', 'bank_rib', 'default_monthly_fee', 'is_transparency_enabled']);

        if ($request->has('contact_phone') || $request->has('contact_email')) {
            $currentContact = is_array($tenant->contact_info) ? $tenant->contact_info : [];
            if ($request->has('contact_phone')) $currentContact['phone'] = $request->contact_phone;
            if ($request->has('contact_email')) $currentContact['email'] = $request->contact_email;
            $updateData['contact_info'] = $currentContact;
        }

        // Feature 9 — Set and lock initial balances
        if (! $tenant->initial_balance_locked) {
            if ($request->has('initial_treasury_balance')) {
                $updateData['initial_treasury_balance'] = $request->initial_treasury_balance;
            }
            if ($request->has('initial_bank_balance')) {
                $updateData['initial_bank_balance'] = $request->initial_bank_balance;
            }
            if ($request->has('initial_treasury_balance') || $request->has('initial_bank_balance')) {
                $updateData['initial_balance_locked'] = true; // Lock permanently
            }
        }

        $tenant->update($updateData);

        // Apply new default monthly fee to existing billable apartments if requested
        $updatedApartmentsCount = 0;
        if ($request->boolean('apply_to_existing_apartments') && $request->has('default_monthly_fee')) {
            $newFee = (float) $request->default_monthly_fee;
            $buildingIds = $tenant->buildings()->pluck('id');
            $updatedApartmentsCount = \App\Models\Apartment::whereIn('building_id', $buildingIds)
                ->where('is_billable', true)
                ->update(['monthly_fee' => $newFee]);
        }

        $fresh = $tenant->fresh();

        return response()->json([
            'message'                  => 'تم تحديث وحفظ إعدادات الإقامة بنجاح.',
            'updated_apartments_count' => $updatedApartmentsCount,
            'tenant'                   => [
                'id'                        => $fresh->id,
                'name'                      => $fresh->name,
                'bank_rib'                  => $fresh->bank_rib,
                'default_monthly_fee'       => (float) ($fresh->default_monthly_fee ?? 200.0),
                'is_transparency_enabled'   => (bool) $fresh->is_transparency_enabled,
                'initial_treasury_balance'  => (float) $fresh->initial_treasury_balance,
                'initial_bank_balance'      => (float) $fresh->initial_bank_balance,
                'initial_balance_locked'    => (bool) $fresh->initial_balance_locked,
                'contact_info'              => $fresh->contact_info,
            ],
        ]);
    }
}
