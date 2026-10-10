<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $phoneRegex = config('smartsyndic.phone_regex', '/^(?:(?:\+|00)212|0)[5-7]\d{8}$/');

        return [
            'invitation_code' => ['required_without:invite_code', 'string', 'exists:tenants,invitation_code'],
            'invite_code'     => ['sometimes', 'string'],
            'first_name'      => ['required', 'string', 'min:2', 'max:100'],
            'last_name'       => ['required', 'string', 'min:2', 'max:100'],
            'email'           => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone'           => ['required', 'string', 'regex:' . $phoneRegex, 'unique:users,phone'],
            'password'        => ['required', 'string', 'min:8', 'max:128'],
            'apartment_id'    => ['required', 'uuid', 'exists:apartments,id'],
            'occupancy_type'  => ['required', 'string', 'in:OWNER,TENANT'],
            'locale'          => ['sometimes', 'string', 'in:ar,fr,en'],
        ];
    }
}
