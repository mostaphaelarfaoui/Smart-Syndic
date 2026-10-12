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
                'property_type'             => $tenant->property_type,
                'bank_rib'                  => $tenant->bank_rib,
                'is_transparency_enabled'   => $tenant->is_transparency_enabled,
                'stamp_url'                 => $tenant->stamp_url, // Feature 1
                'has_stamp'                 => ! empty($tenant->stamp_image_path),
                'initial_treasury_balance'  => (float) $tenant->initial_treasury_balance, // Feature 9
                'initial_bank_balance'      => (float) $tenant->initial_bank_balance,     // Feature 9
                'initial_balance_locked'    => $tenant->initial_balance_locked,           // Feature 9
                'invitation_code'           => $tenant->invitation_code,
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
     * Update general tenant settings (bank RIB, transparency toggle, etc.).
     * Initial treasury balance can only be set if not yet locked (Feature 9).
     */
    public function update(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $rules = [
            'bank_rib'                => ['sometimes', 'nullable', 'string', 'max:34'],
            'is_transparency_enabled' => ['sometimes', 'boolean'],
        ];

        // Feature 9 — Only allow setting initial balances if not yet locked
        if (! $tenant->initial_balance_locked) {
            $rules['initial_treasury_balance'] = ['sometimes', 'numeric', 'min:0'];
            $rules['initial_bank_balance']     = ['sometimes', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        $updateData = $request->only(['bank_rib', 'is_transparency_enabled']);

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

        return response()->json([
            'message' => 'تم تحديث الإعدادات بنجاح.',
            'tenant'  => [
                'bank_rib'                  => $tenant->fresh()->bank_rib,
                'is_transparency_enabled'   => $tenant->is_transparency_enabled,
                'initial_treasury_balance'  => (float) $tenant->initial_treasury_balance,
                'initial_bank_balance'      => (float) $tenant->initial_bank_balance,
                'initial_balance_locked'    => $tenant->initial_balance_locked,
            ],
        ]);
    }
}
