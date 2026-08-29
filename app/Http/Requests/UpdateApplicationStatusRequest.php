<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationStatusRequest extends FormRequest
{
    private const FINAL_STATUSES = [ApplicationStatus::Accepted->value, ApplicationStatus::Rejected->value];

    public function authorize(): bool
    {
        return $this->user()?->hasRole('Recruiter') ?? false;
    }

    public function rules(): array
    {
        $application = $this->route('application');

        $currentStatus = $application
            ? ($application->status instanceof \BackedEnum ? $application->status->value : $application->status)
            : null;

        $rules = [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        // Withdrawn is candidate-owned: recruiters may never set it, only see it.
        $rules['status'][] = function ($attribute, $value, $fail) {
            if ($value === ApplicationStatus::Withdrawn->value) {
                $fail(__('validation.status_withdrawn_not_allowed'));
            }
        };

        // Prevent status changes if already changed to accepted, rejected or withdrawn
        if ($application) {
            // If current status is accepted, rejected or withdrawn, prevent any changes
            if (in_array($currentStatus, [...self::FINAL_STATUSES, ApplicationStatus::Withdrawn->value])) {
                $rules['status'][] = function ($attribute, $value, $fail) use ($currentStatus) {
                    if ($value !== $currentStatus) {
                        $fail('Once an application is '.$currentStatus.', you cannot change the decision.');
                    }
                };
            }

            // Only allow notes when status is being changed to accepted/rejected
            if ($this->filled('notes') && $this->input('notes') !== $application->notes) {
                $newStatus = $this->input('status');

                // If status is already accepted/rejected and notes already exist, prevent changes
                if (in_array($currentStatus, self::FINAL_STATUSES) && $application->notes) {
                    $rules['notes'][] = function ($attribute, $value, $fail) {
                        $fail('Notes have already been added and cannot be modified.');
                    };
                }
                // If not changing to accepted/rejected, don't allow adding notes
                elseif (! in_array($newStatus, self::FINAL_STATUSES)) {
                    $rules['notes'][] = function ($attribute, $value, $fail) {
                        $fail('Notes can only be added when accepting or rejecting an application.');
                    };
                }
            }
        }

        // Interview details: mode-driven, only relevant when moving to interview
        $isInterview = $this->input('status') === ApplicationStatus::Interview->value;

        $rules['interview_mode'] = ['nullable', 'in:online,onsite'];
        $rules['interview_at'] = ['nullable', 'date', 'after:now'];
        $rules['interview_location'] = ['nullable', 'string', 'max:255'];
        $rules['interview_url'] = ['nullable', 'url:http,https', 'max:2048'];
        $rules['interview_instructions'] = ['nullable', 'string', 'max:2000'];

        if ($isInterview) {
            $mode = $this->input('interview_mode', 'onsite');
            $rules['interview_at'] = ['required', 'date', 'after:now'];

            if ($mode === 'online') {
                $rules['interview_url'] = ['required', 'url:http,https', 'max:2048'];
                $rules['interview_location'] = ['nullable', 'string', 'max:255'];
            } else {
                $rules['interview_location'] = ['required', 'string', 'max:255'];
                $rules['interview_url'] = ['nullable', 'url:http,https', 'max:2048'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status.enum' => __('validation.status_invalid'),
            'status.custom' => __('validation.status_withdrawn_not_allowed'),
            'interview_mode.in' => __('validation.interview_mode_invalid'),
            'interview_at.required' => __('validation.interview_at_required'),
            'interview_at.after' => __('validation.interview_at_after'),
            'interview_at.date' => __('validation.interview_at_required'),
            'interview_location.required' => __('validation.interview_location_required'),
            'interview_url.required' => __('validation.interview_url_required'),
            'interview_url.url' => __('validation.interview_url_invalid'),
        ];
    }
}
