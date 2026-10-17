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
            'first_name'        => ['required', 'string', 'min:2', 'max:100'],
            'last_name'         => ['required', 'string', 'min:2', 'max:100'],
            'email'             => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone'             => ['required', 'string', 'regex:' . $phoneRegex, 'unique:users,phone'],
            'password'          => ['required', 'string', 'min:8', 'max:128'],
            'organization_name' => ['required', 'string', 'min:3', 'max:255'],
            'property_type'     => ['required', 'string', 'in:SINGLE_BUILDING,MULTI_BUILDING_COMPLEX'],
            'locale'            => ['sometimes', 'string', 'in:ar,fr,en'],
            'total_units'       => ['nullable', 'integer', 'min:1', 'max:500'],
            'total_floors'      => ['nullable', 'integer', 'min:1', 'max:50'],
            'units_per_floor'   => ['nullable', 'integer', 'min:1', 'max:20'],
            'default_monthly_fee' => ['nullable', 'numeric', 'min:0'],
            'bank_rib'            => ['nullable', 'string', 'max:34'],
            'initial_treasury_balance' => ['nullable', 'numeric', 'min:0'],
            'initial_bank_balance'     => ['nullable', 'numeric', 'min:0'],
            'buildings'         => ['nullable', 'array'],
            'buildings.*.block' => ['nullable', 'string', 'max:100'],
            'buildings.*.name'  => ['nullable', 'string', 'max:100'],
            'buildings.*.total_floors'    => ['nullable', 'integer', 'min:1', 'max:50'],
            'buildings.*.units_per_floor' => ['nullable', 'integer', 'min:1', 'max:20'],
            'buildings.*.total_units'     => ['nullable', 'integer', 'min:1', 'max:500'],
            'buildings.*.monthly_fee'     => ['nullable', 'numeric', 'min:0'],
            'buildings.*.numbering_scheme'=> ['nullable', 'string', 'in:sequential,floor_based,floor_unit'],
            'numbering_scheme'            => ['nullable', 'string', 'in:sequential,floor_based,floor_unit'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = preg_replace('/[\s\-\.\(\)]+/', '', (string) $this->phone);
            // If entered 212... without leading plus
            if (preg_match('/^212[5-7]\d{8}$/', $phone)) {
                $phone = '+' . $phone;
            }
            $this->merge(['phone' => $phone]);
        }

        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->email))]);
        }
    }

    public function messages(): array
    {
        return [
            'first_name.required'        => 'الاسم الشخصي مطلوب.',
            'last_name.required'         => 'الاسم العائلي مطلوب.',
            'email.required'             => 'البريد الإلكتروني مطلوب.',
            'email.email'                => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique'               => 'البريد الإلكتروني مسجل مسبقاً، يرجى تسجيل الدخول أو استخدام بريد آخر.',
            'phone.required'             => 'رقم الهاتف مطلوب.',
            'phone.regex'                => 'صيغة رقم الهاتف غير صحيحة. يرجى إدخال رقم مغربي صحيح (مثال: 0612345678 أو +212612345678).',
            'phone.unique'               => 'رقم الهاتف مسجل مسبقاً لحساب آخر.',
            'password.required'          => 'كلمة المرور مطلوبة.',
            'password.min'               => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
            'organization_name.required' => 'اسم الإقامة أو العمارة مطلوب.',
            'property_type.required'     => 'نوع العقار مطلوب.',
        ];
    }
}
