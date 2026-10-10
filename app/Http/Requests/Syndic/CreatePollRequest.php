<?php

namespace App\Http\Requests\Syndic;

use Illuminate\Foundation\Http\FormRequest;

class CreatePollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminSyndic();
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'min:5', 'max:255'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'expires_at'   => ['required', 'date', 'after:now'],
            'options'      => ['required', 'array', 'min:2', 'max:10'],
            'options.*'    => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
