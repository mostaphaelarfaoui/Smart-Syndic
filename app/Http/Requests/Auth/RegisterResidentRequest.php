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

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $phone = preg_replace('/[\s\-\.\(\)]+/', '', (string) $this->phone);
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
            'first_name.required'      => 'الاسم الشخصي مطلوب.',
            'last_name.required'       => 'الاسم العائلي مطلوب.',
            'email.required'           => 'البريد الإلكتروني مطلوب.',
            'email.email'              => 'صيغة البريد الإلكتروني غير صالحة.',
            'email.unique'             => 'البريد الإلكتروني مسجل مسبقاً، يرجى تسجيل الدخول أو استخدام بريد آخر.',
            'phone.required'           => 'رقم الهاتف مطلوب.',
            'phone.regex'              => 'صيغة رقم الهاتف غير صحيحة. يرجى إدخال رقم مغربي صحيح (مثال: 0612345678 أو +212612345678).',
            'phone.unique'             => 'رقم الهاتف مسجل مسبقاً لحساب آخر.',
            'password.required'        => 'كلمة المرور مطلوبة.',
            'password.min'             => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
            'invitation_code.required_without' => 'رمز الدعوة مطلوب.',
            'invitation_code.exists'   => 'رمز الدعوة غير صحيح أو منتهي الصلاحية.',
            'apartment_id.required'    => 'يرجى اختيار الشقة.',
            'occupancy_type.required'  => 'يرجى تحديد صفتك (مالك أو مكترٍ).',
        ];
    }
}
