<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * PUT /api/v1/auth/profile
     *
     * Updates the authenticated user's profile information.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();
        $passwordChanged = false;

        if (!empty($data['password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'كلمة المرور الحالية غير صحيحة.',
                    'errors'  => [
                        'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
                    ],
                ], 422);
            }
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
            $passwordChanged = true;
        } else {
            unset($data['password'], $data['current_password']);
        }

        $user->update($data);

        if ($passwordChanged) {
            AuditLog::record(
                actorId: $user->id,
                action: 'USER_PASSWORD_UPDATED',
                targetTenantId: $user->tenant_id,
                metadata: [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'role'    => $user->role,
                    'source'  => 'MOBILE_API',
                ]
            );
        }

        return response()->json([
            'message' => __('auth.profile_updated', [], $user->locale ?? 'ar'),
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'                     => $user->role,
                'status'                   => $user->status,
                'locale'                   => $user->locale,
                'theme_preference'         => $user->theme_preference ?? 'light',
                'notification_preferences' => $user->notification_preferences ?? [
                    'payments'      => true,
                    'residents'     => true,
                    'polls'         => true,
                    'announcements' => true,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout-other-devices
     *
     * Revokes all tokens except the current one and terminates web sessions.
     */
    public function logoutOtherDevices(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // Revoke all other personal access tokens
        if ($currentToken) {
            \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)
                ->where('id', '!=', $currentToken->id)
                ->delete();
        } else {
            \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)->delete();
        }

        // Also terminate any active web sessions
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        AuditLog::record(
            actorId: $user->id,
            action: 'USER_OTHER_SESSIONS_REVOKED',
            targetTenantId: $user->tenant_id,
            metadata: [
                'source' => 'MOBILE_API',
                'ip'     => $request->ip(),
            ]
        );

        return response()->json([
            'message' => 'تم إنهاء الجلسات وتسجيل الخروج من جميع الأجهزة الأخرى بنجاح.',
        ]);
    }
}
