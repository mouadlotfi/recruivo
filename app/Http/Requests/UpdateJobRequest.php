<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Recruiter') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'gte:salary_min'],
            'category' => ['nullable', 'string', 'max:100'],
            'remote_type' => ['sometimes', 'required', 'in:remote,hybrid,onsite'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
        ];
    }
}
