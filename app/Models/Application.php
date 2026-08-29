<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'job_id',
        'cover_letter',
        'resume_path',
        'status',
        'notes',
        'status_changed',
        'notes_added',
        'status_changed_at',
        'notes_added_at',
        'original_status',
        'interview_at',
        'interview_mode',
        'interview_location',
        'interview_url',
        'interview_instructions',
    ];

    protected $casts = [
        'status' => ApplicationStatus::class,
        'status_changed' => 'boolean',
        'notes_added' => 'boolean',
        'status_changed_at' => 'datetime',
        'notes_added_at' => 'datetime',
        'interview_at' => 'datetime',
        'interview_mode' => 'string',
    ];

    protected static function booted(): void
    {
        static::created(function (Application $application) {
            $application->statusEvents()->create([
                'changed_by_user_id' => auth()->id() ?: $application->candidate_id,
                'from_status' => null,
                'to_status' => $application->statusValue(),
                'created_at' => $application->created_at ?? now(),
            ]);
        });

        static::updated(function (Application $application) {
            if (! $application->wasChanged('status')) {
                return;
            }

            $application->statusEvents()->create([
                'changed_by_user_id' => auth()->id(),
                'from_status' => $application->getRawOriginal('status'),
                'to_status' => $application->statusValue(),
                'created_at' => now(),
            ]);
        });
    }

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function statusEvents()
    {
        return $this->hasMany(ApplicationStatusEvent::class)->oldest('created_at')->oldest('id');
    }

    public function applyStatusUpdate(array $attributes): void
    {
        $updateData = $attributes;

        if (array_key_exists('status', $attributes)) {
            $currentStatus = $this->status instanceof \BackedEnum
                ? $this->status->value
                : $this->status;

            // Only mark as changed if moving to accepted/rejected
            if ($attributes['status'] !== $currentStatus && in_array($attributes['status'], ['accepted', 'rejected'])) {
                $updateData['status_changed'] = true;
                $updateData['status_changed_at'] = now();
            }
        }

        if (array_key_exists('notes', $attributes) && filled($attributes['notes']) && ! $this->notes) {
            $updateData['notes_added'] = true;
            $updateData['notes_added_at'] = now();
        }

        // Moving away from Interview: clear interview details (root cause fix for web + API)
        $targetStatus = $attributes['status'] ?? ($this->status instanceof \BackedEnum ? $this->status->value : $this->status);
        if ($targetStatus !== ApplicationStatus::Interview->value) {
            $updateData['interview_at'] = null;
            $updateData['interview_location'] = null;
            $updateData['interview_url'] = null;
            $updateData['interview_instructions'] = null;
        }

        $this->update($updateData);
    }

    private function statusValue(): string
    {
        if ($this->status instanceof \BackedEnum) {
            return $this->status->value;
        }

        return (string) ($this->status ?? ApplicationStatus::Pending->value);
    }
}
