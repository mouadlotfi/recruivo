<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Temporarily bypass authorization
    }

    public function rules(): array
    {
        return [
            'resume' => ['nullable', 'file'],
            'cover_letter' => ['nullable', 'string'],
        ];
    }
}
