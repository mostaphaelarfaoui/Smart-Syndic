<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyPasswordController extends Controller
{
    /**
     * Verify the current authenticated user's password before performing a sensitive action.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'valid'   => false,
                'message' => 'كلمة المرور غير صحيحة. يرجى التأكد من كتابة كلمة المرور وإعادة المحاولة.',
            ], 422);
        }

        return response()->json([
            'valid'   => true,
            'message' => 'تم التحقق من كلمة المرور بنجاح.',
        ]);
    }
}
