<?php

namespace App\Http\Requests\Syndic;

use Illuminate\Foundation\Http\FormRequest;

class RecordExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminSyndic();
    }

    public function rules(): array
    {
        return [
            'building_id'        => ['nullable', 'uuid', 'exists:buildings,id'],
            'category'           => ['required', 'string', 'in:ELEVATOR,CLEANING,SECURITY,WATER_ELECTRICITY,GARDENING,MAINTENANCE,OTHER'],
            'title'              => ['required', 'string', 'min:3', 'max:255'],
            'amount'             => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_method'     => ['sometimes', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE'],
            'expense_date'       => ['required', 'date', 'before_or_equal:today'],
            'invoice_proof'      => ['nullable', 'file', 'max:5120'],
            'invoice_proof_path' => ['nullable', 'string'],
        ];
    }
}
