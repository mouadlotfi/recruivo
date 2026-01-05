<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, MustVerifyEmail;

    protected $fillable = [
        'company_id',
        'is_recruiter',
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
    ];

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

    public function candidateProfile()
    {
        return $this->hasOne(CandidateProfile::class, 'user_id');
    }

    /**
     * Get the profile picture URL.
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture_path) {
            return null;
        }

        return asset('storage/' . $this->profile_picture_path);
    }

    /**
     * Get the resume URL for candidates.
     */
    public function getResumeUrlAttribute(): ?string
    {
        if (!$this->candidateProfile?->resume_path) {
            return null;
        }

        return asset('storage/' . $this->candidateProfile->resume_path);
    }

    /**
     * Get the email verification URL that points to the frontend.
     */
    public function getEmailVerificationUrl(): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        
        // Generate the signed URL with all parameters
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );
        
        // Parse the URL to extract parameters
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $query = $parsedUrl['query'] ?? '';
        parse_str($query, $params);
        
        // Extract id and hash from the path (format: /email/verify/{id}/{hash})
        $pathParts = explode('/', trim($path, '/'));
        $id = $pathParts[2] ?? ''; // /email/verify/{id}/{hash}
        $hash = $pathParts[3] ?? '';
        
        // Build the frontend URL with proper parameters
        $frontendVerificationUrl = $frontendUrl . '/email-verify?' . http_build_query([
            'id' => $id,
            'hash' => $hash,
            'expires' => $params['expires'] ?? '',
            'signature' => $params['signature'] ?? '',
        ]);
        
        return $frontendVerificationUrl;
    }

    /**
     * Get the email verification URL for email changes (redirects to profile).
     */
    public function getEmailChangeVerificationUrl(): string
    {
        // Generate backend route URL
        $url = route('profile.email.verify', [
            'locale' => app()->getLocale(),
            'id' => $this->getKey(),
            'hash' => sha1($this->pending_email ?? $this->email),
        ]);
        
        return $url;
    }

    /**
     * Check if this user is a recruiter.
     */
    public function isRecruiter(): bool
    {
        return $this->is_recruiter || $this->hasRole('Recruiter');
    }

    /**
     * Check if this user is a candidate.
     */
    public function isCandidate(): bool
    {
        return !$this->isRecruiter() && $this->hasRole('Candidate');
    }

    /**
     * Get the display name for the user.
     * For recruiters, use company name. For candidates, use personal name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->isRecruiter() && $this->company) {
            return $this->company->name;
        }
        
        return $this->name ?? 'Unknown User';
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        // Only send if user hasn't verified their email yet
        if ($this->hasVerifiedEmail()) {
            return;
        }

        $this->notify(new VerifyEmailNotification);
    }
}
