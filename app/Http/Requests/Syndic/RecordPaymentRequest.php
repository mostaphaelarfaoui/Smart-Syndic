<?php

namespace App\Http\Requests\Syndic;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminSyndic();
    }

    public function rules(): array
    {
        return [
            'apartment_id'      => ['required', 'uuid', 'exists:apartments,id'],
            'amount_paid'       => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method'    => ['required', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE'],
            'cheque_reference'  => ['required_if:payment_method,CHEQUE', 'nullable', 'string', 'max:100'],
            'payment_date'      => ['required', 'date', 'before_or_equal:today'],
            'covered_months'    => ['required', 'array', 'min:1', 'max:24'],
            'covered_months.*'  => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'bank_receipt_proof' => ['nullable', 'file', 'max:5120'],
        ];
    }
}
