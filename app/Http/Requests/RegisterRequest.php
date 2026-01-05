<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $accountType = $this->input('account_type');
        $companyData = $this->input('company');

        if ($accountType !== 'company') {
            $this->merge([
                'company' => null,
            ]);

            return;
        }

        if (is_array($companyData)) {
            $normalized = [];
            foreach ($companyData as $key => $value) {
                if (is_string($value)) {
                    $value = trim($value);
                }

                $normalized[$key] = $value === '' ? null : $value;
            }

            $this->merge([
                'company' => $normalized,
            ]);
        }
    }

    public function rules(): array
    {
        $email = $this->input('email');
        // Extract username from email (part before @)
        $username = $email ? explode('@', $email)[0] : null;

        return [
            'account_type' => ['required', Rule::in(['candidate', 'company'])],
            'name' => ['required_if:account_type,candidate', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword($username)],
            'phone' => ['nullable', 'string', 'max:20'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'], // 5MB max
            'company.name' => ['required_if:account_type,company', 'string', 'max:255'],
            'company.tagline' => ['nullable', 'string', 'max:255'],
            'company.location' => ['nullable', 'string', 'max:255'],
            'company.website_url' => ['nullable', 'url', 'max:255'],
            'company.linkedin_url' => ['nullable', 'url', 'max:255'],
            'company.size' => ['nullable', 'string', 'max:50'],
            'company.job_title' => ['nullable', 'string', 'max:120'],
            'company.mission' => ['nullable', 'string'],
            'company.culture' => ['nullable', 'string'],
            'company.email' => ['required_if:account_type,company', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'company.name.required_if' => __('validation.company_name_required'),
        ];
    }
}
