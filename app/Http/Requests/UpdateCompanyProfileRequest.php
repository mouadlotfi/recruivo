<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'tagline' => 'sometimes|nullable|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'website_url' => 'sometimes|nullable|url|max:255',
            'linkedin_url' => 'sometimes|nullable|url|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'size' => 'sometimes|nullable|string|max:50',
            'founded_year' => 'sometimes|nullable|integer|min:1800|max:' . date('Y'),
            'mission' => 'sometimes|nullable|string',
            'culture' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => __('validation.company_name_max', ['max' => 255]),
            'tagline.max' => __('validation.tagline_max', ['max' => 255]),
            'location.max' => __('validation.location_max', ['max' => 255]),
            'website_url.url' => __('validation.website_url_invalid'),
            'linkedin_url.url' => __('validation.linkedin_url_invalid'),
            'email.email' => __('validation.email_invalid'),
            'size.max' => __('validation.company_size_max', ['max' => 50]),
            'founded_year.integer' => __('validation.founded_year_invalid'),
            'founded_year.min' => __('validation.founded_year_min', ['min' => 1800]),
            'founded_year.max' => __('validation.founded_year_future'),
        ];
    }
}
