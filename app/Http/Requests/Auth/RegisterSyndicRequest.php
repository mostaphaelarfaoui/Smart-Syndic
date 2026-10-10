<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSyndicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $phoneRegex = config('smartsyndic.phone_regex');

        return [
            'first_name'     => ['required', 'string', 'min:2', 'max:100'],
            'last_name'      => ['required', 'string', 'min:2', 'max:100'],
            'email'          => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone'          => ['required', 'string', 'regex:' . $phoneRegex, 'unique:users,phone'],
            'password'       => ['required', 'string', 'min:8', 'max:128'],
            'organization_name' => ['required', 'string', 'min:3', 'max:255'],
            'property_type'  => ['required', 'string', 'in:SINGLE_BUILDING,MULTI_BUILDING_COMPLEX'],
            'locale'         => ['sometimes', 'string', 'in:ar,fr,en'],
        ];
    }
}
