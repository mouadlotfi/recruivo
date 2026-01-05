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
    ];

    protected $casts = [
        'status' => ApplicationStatus::class,
        'status_changed' => 'boolean',
        'notes_added' => 'boolean',
        'status_changed_at' => 'datetime',
        'notes_added_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
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

        if (array_key_exists('notes', $attributes) && filled($attributes['notes']) && !$this->notes) {
            $updateData['notes_added'] = true;
            $updateData['notes_added_at'] = now();
        }

        $this->update($updateData);
    }
}
