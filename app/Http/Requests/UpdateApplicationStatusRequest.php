<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Recruiter') ?? false;
    }

    public function rules(): array
    {
        $application = $this->route('application');
        
        $currentStatus = null;
        $notesRules = ['nullable', 'string', 'max:2000'];

        if ($application) {
            $currentStatus = $application->status instanceof \BackedEnum
                ? $application->status->value
                : $application->status;

            $newStatus = $this->input('status');
            $isStatusBeingChanged = $this->filled('status') && $newStatus !== $currentStatus;

            // If changing status to accepted or rejected, notes are required
            if ($isStatusBeingChanged && in_array($newStatus, ['accepted', 'rejected'])) {
                $notesRules = ['required', 'string', 'max:2000'];
            }
        }

        $rules = [
            'status' => ['required', 'in:pending,accepted,rejected'],
            'notes' => $notesRules,
        ];

        // Prevent status changes if already changed to accepted or rejected
        if ($application) {
            $currentStatus = $application->status instanceof \BackedEnum
                ? $application->status->value
                : $application->status;

            // If current status is accepted or rejected, prevent any changes
            if (in_array($currentStatus, ['accepted', 'rejected'])) {
                $rules['status'][] = function ($attribute, $value, $fail) use ($currentStatus) {
                    if ($value !== $currentStatus) {
                        $fail('Once an application is ' . $currentStatus . ', you cannot change the decision.');
                    }
                };
            }

            // Only allow notes when status is being changed to accepted/rejected
            if ($this->filled('notes') && $this->input('notes') !== $application->notes) {
                $newStatus = $this->input('status');
                
                // If status is already accepted/rejected and notes already exist, prevent changes
                if (in_array($currentStatus, ['accepted', 'rejected']) && $application->notes) {
                    $rules['notes'][] = function ($attribute, $value, $fail) {
                        $fail('Notes have already been added and cannot be modified.');
                    };
                }
                // If not changing to accepted/rejected, don't allow adding notes
                else if (!in_array($newStatus, ['accepted', 'rejected'])) {
                    $rules['notes'][] = function ($attribute, $value, $fail) {
                        $fail('Notes can only be added when accepting or rejecting an application.');
                    };
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'notes.required' => __('validation.notes_required_status'),
            'status.in' => __('validation.status_invalid'),
        ];
    }
}
