<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Candidate') ?? false;
    }

    public function rules(): array
    {
        return [
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'cover_letter' => ['required', 'string', 'max:10000'],
        ];
    }
}
