<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    protected $fillable = [
        'company_id',
        'is_recruiter',
        'is_demo',
        'name',
        'email',
        'password',
        'profile_summary',
        'location',
        'phone',
        'job_title',
        'profile_picture_path',
        'email_verified_at',
        'pending_email',
        'email_verification_token',
        'email_change_requested_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'email_change_requested_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->is_demo) {
                throw ValidationException::withMessages([
                    'account' => __('common.demo_account_cannot_be_deleted'),
                ]);
            }
        });
    }

    public function jobs()
    {
        return $this->hasMany(Job::class, 'recruiter_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'candidate_id');
    }

    public function noteTemplates()
    {
        return $this->hasMany(RecruiterNoteTemplate::class, 'recruiter_id');
    }

    public function savedJobs()
    {
        return $this->belongsToMany(Job::class, 'saved_jobs')->withTimestamps();
    }

    public function candidateProfile()
    {
        return $this->hasOne(CandidateProfile::class, 'user_id');
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if (! $this->profile_picture_path) {
            return null;
        }

        return asset('storage/'.$this->profile_picture_path);
    }

    public function getResumeUrlAttribute(): ?string
    {
        if (! $this->candidateProfile?->resume_path) {
            return null;
        }

        return route('api.candidate.resume');
    }

    public function getEmailVerificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'locale' => app()->getLocale() ?: config('locales.default', 'en'),
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );
    }

    /**
     * Get the email verification URL for email changes (redirects to profile).
     */
    public function getEmailChangeVerificationUrl(): string
    {
        $url = URL::temporarySignedRoute('profile.email.verify', now()->addHour(), [
            'locale' => app()->getLocale(),
            'id' => $this->getKey(),
            'hash' => sha1($this->pending_email ?? $this->email),
        ]);

        return $url;
    }

    public function isRecruiter(): bool
    {
        return $this->is_recruiter || $this->hasRole('Recruiter');
    }

    /**
     * Compute profile completion score from 5 signals (20% each).
     */
    public function profileCompletion(): array
    {
        $profile = $this->relationLoaded('candidateProfile')
            ? $this->candidateProfile
            : $this->candidateProfile()->first();

        $items = [
            'headline' => filled($profile?->headline),
            'profile_summary' => filled($this->profile_summary),
            'skills' => filled($profile?->skills),
            'resume' => filled($profile?->resume_path),
            'experience' => ! empty($profile?->experiences),
        ];

        $completed = count(array_filter($items));

        return [
            'percentage' => $completed * 20,
            'completed' => $completed,
            'total' => count($items),
            'missing' => array_keys(array_filter($items, fn (bool $complete) => ! $complete)),
        ];
    }

    public function isCandidate(): bool
    {
        return ! $this->isRecruiter() && $this->hasRole('Candidate');
    }

    /**
     * For recruiters, use company name. For candidates, use personal name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->isRecruiter() && $this->company) {
            return $this->company->name;
        }

        return $this->name ?? 'Unknown User';
    }

    public function sendEmailVerificationNotification()
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }

        $this->notify(new VerifyEmailNotification);
    }
}
