<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        \Log::info('UpdateProfileRequest validation rules', [
            'user_id' => $this->user()?->id,
            'request_data' => $this->all(),
            'files' => $this->allFiles()
        ]);

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $this->user()->id,
            'location' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'profile_summary' => 'sometimes|nullable|string|max:1000',
            'resume' => 'sometimes|nullable|file|mimes:pdf,doc,docx|max:5120',
            // Company fields for recruiters
            'company.name' => 'sometimes|string|max:255',
            'company.tagline' => 'sometimes|nullable|string|max:255',
            'company.email' => 'sometimes|nullable|email|max:255',
            'company.location' => 'sometimes|nullable|string|max:255',
            'company.website_url' => 'sometimes|nullable|url|max:255',
            'company.linkedin_url' => 'sometimes|nullable|url|max:255',
            'company.mission' => 'sometimes|nullable|string',
            'company.culture' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('validation.email_taken'),
            'email.email' => __('validation.email_invalid'),
            'name.max' => __('validation.name_max', ['max' => 255]),
            'location.max' => __('validation.location_max', ['max' => 255]),
            'phone.max' => __('validation.phone_max', ['max' => 20]),
            'profile_summary.max' => __('validation.profile_summary_max', ['max' => 1000]),
        ];
    }
}
