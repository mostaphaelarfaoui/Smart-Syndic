<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyInviteCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'invitation_code' => ['required_without:invite_code', 'string', 'min:4', 'max:32'],
            'invite_code'     => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'invitation_code.exists' => __('auth.invalid_invite_code'),
        ];
    }
}
