<?php

namespace App\Http\Requests\Resident;

use Illuminate\Foundation\Http\FormRequest;

class CastVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isResident();
    }

    public function rules(): array
    {
        return [
            'poll_option_id'       => ['required', 'uuid', 'exists:poll_options,id'],
            'apartment_id'         => ['required', 'uuid', 'exists:apartments,id'],
            'comment'              => ['nullable', 'string', 'max:1000'],
            'is_anonymous_comment' => ['nullable', 'boolean'],
        ];
    }
}
