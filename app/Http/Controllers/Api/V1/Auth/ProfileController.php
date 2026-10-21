<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
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
        } else {
            unset($data['password'], $data['current_password']);
        }

        $user->update($data);

        return response()->json([
            'message' => __('auth.profile_updated', [], $user->locale ?? 'ar'),
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'status'     => $user->status,
                'locale'     => $user->locale,
            ],
        ]);
    }
}
