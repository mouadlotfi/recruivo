<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Recruiter') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'salary_min' => ['required', 'integer', 'min:0'],
            'salary_max' => ['required', 'integer', 'gte:salary_min'],
            'category' => ['required', 'string', 'max:100'],
            'remote_type' => ['required', 'in:remote,hybrid,onsite'],
            'status' => ['required', 'in:draft,published'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
