<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name'  => ['sometimes', 'string', 'max:255'],
            'phone'      => ['sometimes', 'string', 'max:20'],
            'password'   => ['sometimes', 'string', 'min:8', 'confirmed'],
            'locale'     => ['sometimes', 'string', 'in:ar,fr,en'],
        ];
    }
}
