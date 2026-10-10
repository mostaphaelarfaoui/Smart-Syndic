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

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Block inactive accounts (except Super Admins)
        if ($user->status !== 'ACTIVE' && ! $user->isSuperAdmin()) {
            return response()->json([
                'message' => __('auth.account_inactive'),
                'status'  => $user->status,
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
