<?php

namespace App\Http\Requests\Resident;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isResident();
    }

    public function rules(): array
    {
        return [
            'apartment_id' => ['required', 'uuid', 'exists:apartments,id'],
            'title'        => ['required', 'string', 'min:5', 'max:255'],
            'description'  => ['required', 'string', 'min:10', 'max:5000'],
            'attachment'   => ['nullable', 'file', 'max:5120'],
        ];
    }
}
