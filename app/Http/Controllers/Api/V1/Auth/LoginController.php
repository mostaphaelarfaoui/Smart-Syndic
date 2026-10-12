<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/v1/auth/login
     *
     * Authenticates a user and returns a Bearer token with role/tenant metadata.
     */
    public function __invoke(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // 1. Email not found
        if (! $user) {
            return response()->json([
                'error_type' => 'EMAIL_NOT_FOUND',
                'message'    => 'البريد الإلكتروني غير مسجل في النظام. يرجى التأكد من كتابة البريد بشكل صحيح أو إنشاء حساب جديد.',
                'errors'     => [
                    'email' => ['البريد الإلكتروني غير مسجل في النظام.'],
                ],
            ], 422);
        }

        // 2. Wrong password
        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error_type' => 'INVALID_PASSWORD',
                'message'    => 'كلمة المرور غير صحيحة. يرجى التأكد من كلمة المرور والمحاولة مرة أخرى.',
                'errors'     => [
                    'password' => ['كلمة المرور غير صحيحة.'],
                ],
            ], 422);
        }

        // 3. Block inactive or pending accounts (except Super Admins)
        if ($user->status !== 'ACTIVE' && ! $user->isSuperAdmin()) {
            if ($user->isResident()) {
                $apartment = $user->apartments()->with('building')->first();
                $tenant = $user->tenant;
                $syndic = $tenant ? User::where('tenant_id', $tenant->id)->where('role', 'ADMIN_SYNDIC')->first() : null;

                $isPending = $user->status === 'PENDING';
                $message = $isPending
                    ? 'طلب انضمامك قيد المراجعة حالياً من طرف سانديك الإقامة.'
                    : 'حسابك معطل أو موقوف حالياً. يرجى التواصل مع سانديك الإقامة لإعادة تفعيله.';

                return response()->json([
                    'error_type'    => $isPending ? 'RESIDENT_PENDING_APPROVAL' : 'RESIDENT_SUSPENDED',
                    'message'       => $message,
                    'status'        => $user->status,
                    'role'          => 'RESIDENT',
                    'user'          => [
                        'name'  => $user->full_name,
                        'email' => $user->email,
                    ],
                    'residence'     => $tenant ? [
                        'id'   => $tenant->id,
                        'name' => $tenant->name,
                    ] : null,
                    'apartment'     => $apartment ? [
                        'id'          => $apartment->id,
                        'unit_number' => $apartment->unit_number,
                        'building'    => $apartment->building?->name,
                    ] : null,
                    'syndic'        => $syndic ? [
                        'name'  => $syndic->full_name,
                        'phone' => $syndic->phone,
                        'email' => $syndic->email,
                    ] : null,
                ], 403);
            }

            // For syndics awaiting approval or suspended
            $contactInfo = null;
            if ($user->isAdminSyndic()) {
                $contactInfo = $user->tenant?->contact_info ?? [
                    'email'    => config('smartsyndic.admin_contact.email', 'support@smartsyndic.com'),
                    'phone'    => config('smartsyndic.admin_contact.phone', '+212 600 000 000'),
                    'whatsapp' => config('smartsyndic.admin_contact.whatsapp', '+212 600 000 000'),
                ];
            }

            $isPending = $user->status === 'PENDING';
            $message = $isPending
                ? 'حساب السانديك قيد التحقق والتفعيل من قبل إدارة المنصة.'
                : 'تم إيقاف حسابك مؤقتاً. يرجى التواصل مع إدارة المنصة للاستفسار.';

            return response()->json([
                'error_type'    => $isPending ? 'SYNDIC_PENDING_APPROVAL' : 'SYNDIC_SUSPENDED',
                'message'       => $message,
                'status'        => $user->status,
                'role'          => $user->role,
                'tenant_status' => $user->tenant?->status,
                'contact_info'  => $contactInfo,
            ], 403);
        }

        // Revoke previous tokens for security
        $user->tokens()->delete();

        // Create new Sanctum token
        $token = $user->createToken('api-token', [$user->role])->plainTextToken;

        return response()->json([
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'locale'     => $user->locale,
                'theme'      => $user->theme_preference,
                'status'     => $user->status,
            ],
            'tenant'  => $user->tenant ? [
                'id'                      => $user->tenant->id,
                'name'                    => $user->tenant->name,
                'property_type'           => $user->tenant->property_type,
                'plan_type'               => $user->tenant->plan_type,
                'is_transparency_enabled' => $user->tenant->is_transparency_enabled,
            ] : null,
        ]);
    }
}
