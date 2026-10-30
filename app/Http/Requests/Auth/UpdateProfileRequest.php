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
            'first_name'       => ['sometimes', 'string', 'max:255'],
            'last_name'        => ['sometimes', 'string', 'max:255'],
            'phone'            => ['sometimes', 'string', 'max:20'],
            'current_password' => ['required_with:password', 'nullable', 'string'],
            'password'         => ['required_with:current_password', 'nullable', 'string', 'min:8', 'confirmed'],
            'locale'                     => ['sometimes', 'string', 'in:ar,fr,en'],
            'theme_preference'           => ['sometimes', 'string', 'in:light,dark,system'],
            'notification_preferences'   => ['sometimes', 'array'],
        ];
    }

    /**
     * Custom validation messages in Arabic.
     */
    public function messages(): array
    {
        return [
            'current_password.required_with' => 'يرجى إدخال كلمة المرور الحالية لتغيير كلمة المرور.',
            'password.required_with'         => 'يرجى إدخال كلمة المرور الجديدة وتأكيدها.',
            'password.min'                   => 'يجب أن لا تقل كلمة المرور الجديدة عن 8 أحرف.',
            'password.confirmed'             => 'تأكيد كلمة المرور الجديدة غير متطابق.',
        ];
    }
}
