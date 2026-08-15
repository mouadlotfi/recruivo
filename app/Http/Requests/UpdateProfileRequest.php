<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    private const PROFICIENCY_LEVELS = [
        'beginner',
        'elementary',
        'intermediate',
        'professional_working',
        'fluent',
        'native_bilingual',
    ];

    private const LINK_TYPES = [
        'LinkedIn',
        'X',
        'GitHub',
        'Personal Website',
        'Instagram',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $this->user()->id,
            'location' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'profile_summary' => 'sometimes|nullable|string|max:1000',
            'headline' => 'sometimes|nullable|string|max:255',
            'skills' => 'sometimes|nullable|string|max:2000',
            'languages_json' => ['sometimes', 'nullable', 'json'],
            'links_json' => ['sometimes', 'nullable', 'json'],
            'experiences_json' => ['sometimes', 'nullable', 'json'],
            'educations_json' => ['sometimes', 'nullable', 'json'],
            'languages' => ['sometimes', 'array', 'max:20'],
            'languages.*.language' => ['required', 'string', 'max:100'],
            'languages.*.proficiency' => ['required', Rule::in(self::PROFICIENCY_LEVELS)],
            'links' => ['sometimes', 'array', 'max:5'],
            'links.*.name' => ['required', Rule::in(self::LINK_TYPES)],
            'links.*.url' => ['required', 'url:http,https', 'max:500'],
            'experiences' => ['sometimes', 'array', 'max:20'],
            'experiences.*.job_title' => ['required', 'string', 'max:150'],
            'experiences.*.company_name' => ['required', 'string', 'max:150'],
            'experiences.*.location' => ['nullable', 'string', 'max:150'],
            'experiences.*.start_date' => ['required', 'date_format:Y-m'],
            'experiences.*.end_date' => ['nullable', 'date_format:Y-m'],
            'experiences.*.is_current' => ['required', 'boolean'],
            'experiences.*.description' => ['nullable', 'string', 'max:3000'],
            'educations' => ['sometimes', 'array', 'max:20'],
            'educations.*.school' => ['required', 'string', 'max:150'],
            'educations.*.degree' => ['required', 'string', 'max:150'],
            'educations.*.field_of_study' => ['required', 'string', 'max:150'],
            'educations.*.start_date' => ['required', 'date_format:Y-m'],
            'educations.*.end_date' => ['nullable', 'date_format:Y-m'],
            'educations.*.is_current' => ['required', 'boolean'],
            'educations.*.description' => ['nullable', 'string', 'max:3000'],
            'preferred_categories' => ['nullable', 'array', 'distinct'],
            'preferred_categories.*' => ['string', 'in:' . implode(',', \App\Enums\ItCategory::values())],
            'resume' => 'sometimes|nullable|file|mimes:pdf,doc,docx|max:5120',
            'company.name' => 'sometimes|string|max:255',
            'company.tagline' => 'sometimes|nullable|string|max:255',

            'company.location' => 'sometimes|nullable|string|max:255',
            'company.website_url' => 'sometimes|nullable|url|max:255',
            'company.linkedin_url' => 'sometimes|nullable|url|max:255',
            'company.mission' => 'sometimes|nullable|string',
            'company.culture' => 'sometimes|nullable|string',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    protected function prepareForValidation(): void
    {
        // A hidden sentinel input keeps the array present even when every
        // checkbox is unchecked; drop it so an empty selection means [].
        if ($this->has('preferred_categories')) {
            $this->merge([
                'preferred_categories' => array_values(array_filter(
                    $this->input('preferred_categories', []),
                    fn ($value) => $value !== '' && $value !== null,
                )),
            ]);
        }

        foreach ([
            'languages_json' => 'languages',
            'links_json' => 'links',
            'experiences_json' => 'experiences',
            'educations_json' => 'educations',
        ] as $jsonField => $collectionField) {
            if (!$this->exists($jsonField)) {
                continue;
            }

            $decoded = json_decode((string) $this->input($jsonField), true);
            $this->merge([$collectionField => is_array($decoded) ? $decoded : null]);
        }
    }

    public function after(): array
    {
        return [function ($validator) {
            $linkNames = [];
            foreach ((array) $this->input('links', []) as $index => $link) {
                $name = $link['name'] ?? null;
                if ($name !== null && in_array($name, $linkNames, true)) {
                    $validator->errors()->add("links.{$index}.name", __('profile.link_type_unique'));
                }
                $linkNames[] = $name;
            }

            foreach ((array) $this->input('experiences', []) as $index => $experience) {
                $current = filter_var($experience['is_current'] ?? false, FILTER_VALIDATE_BOOL);
                $currentYear = (int) date('Y');
                foreach (['start_date', 'end_date'] as $field) {
                    $year = (int) substr((string) ($experience[$field] ?? ''), 0, 4);
                    if ($year > $currentYear) {
                        $validator->errors()->add("experiences.{$index}.{$field}", __('profile.year_cannot_be_future'));
                    }
                }
                if (!$current && blank($experience['end_date'] ?? null)) {
                    $validator->errors()->add("experiences.{$index}.end_date", __('validation.required'));
                }
                if (!$current && filled($experience['start_date'] ?? null) && filled($experience['end_date'] ?? null)
                    && $experience['end_date'] < $experience['start_date']) {
                    $validator->errors()->add("experiences.{$index}.end_date", __('profile.end_date_after_start'));
                }
            }

            foreach ((array) $this->input('educations', []) as $index => $education) {
                $current = filter_var($education['is_current'] ?? false, FILTER_VALIDATE_BOOL);
                $startYear = (int) substr((string) ($education['start_date'] ?? ''), 0, 4);
                if ($startYear > (int) date('Y')) {
                    $validator->errors()->add("educations.{$index}.start_date", __('profile.year_cannot_be_future'));
                }
                if (!$current && blank($education['end_date'] ?? null)) {
                    $validator->errors()->add("educations.{$index}.end_date", __('validation.required'));
                }
                if (!$current && filled($education['start_date'] ?? null) && filled($education['end_date'] ?? null)
                    && $education['end_date'] < $education['start_date']) {
                    $validator->errors()->add("educations.{$index}.end_date", __('profile.end_date_after_start'));
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'email.unique' => __('validation.email_taken'),
            'email.email' => __('validation.email_invalid'),
            'name.max' => __('validation.name_max', ['max' => 255]),
            'location.max' => __('validation.location_max', ['max' => 255]),
            'phone.max' => __('validation.phone_max', ['max' => 20]),
            'profile_summary.max' => __('validation.profile_summary_max', ['max' => 1000]),
            'links.max' => __('profile.links_max'),
            'links.*.url.url' => __('profile.valid_http_url'),
        ];
    }
}
