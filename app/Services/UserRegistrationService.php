<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRegistrationService
{
    /**
     * Register a new user (candidate or recruiter).
     *
     * @param array $data Validated registration data
     * @param UploadedFile|null $resumeFile Optional resume file for candidates
     * @return User
     */
    public function register(array $data, ?UploadedFile $resumeFile = null): User
    {
        $accountType = $data['account_type'];
        $isCompanyAccount = $accountType === 'company';

        $company = null;
        if ($isCompanyAccount) {
            $company = $this->createCompany($data['company'] ?? []);
        }

        $user = $this->createUser($data, $company?->id, $isCompanyAccount);
        
        $this->assignRole($user, $isCompanyAccount);

        if (!$isCompanyAccount && $resumeFile) {
            $this->createCandidateProfile($user, $resumeFile);
        }

        return $user;
    }

    /**
     * Create a new company.
     *
     * @param array $companyData
     * @return Company
     */
    protected function createCompany(array $companyData): Company
    {
        $companyData = Arr::only($companyData, [
            'name',
            'tagline',
            'location',
            'website_url',
            'linkedin_url',
            'size',
            'mission',
            'culture',
            'email',
        ]);

        // Filter out empty values
        $companyData = array_filter($companyData, fn ($value) => !is_null($value) && $value !== '');
        
        $companyName = $companyData['name'];

        return Company::create(array_merge($companyData, [
            'name' => $companyName,
            'slug' => Company::generateUniqueSlug($companyName),
        ]));
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @param int|null $companyId
     * @param bool $isRecruiter
     * @return User
     */
    protected function createUser(array $data, ?int $companyId, bool $isRecruiter): User
    {
        $userData = [
            'password' => Hash::make($data['password']),
            'company_id' => $companyId,
            'is_recruiter' => $isRecruiter,
            'email_verified_at' => null, // Require email verification
        ];

        if ($isRecruiter) {
            // For recruiters, use personal email for login
            $userData['email'] = $data['email'];
            $userData['name'] = $data['name'] ?? null;
            $userData['phone'] = null;
            $userData['job_title'] = Arr::get($data, 'company.job_title');
        } else {
            // For candidates, use personal info
            $userData['name'] = $data['name'];
            $userData['email'] = $data['email'];
            $userData['phone'] = $data['phone'] ?? null;
            $userData['job_title'] = null;
        }

        return User::create($userData);
    }

    /**
     * Assign the appropriate role to the user.
     *
     * @param User $user
     * @param bool $isRecruiter
     * @return void
     */
    protected function assignRole(User $user, bool $isRecruiter): void
    {
        $roleName = $isRecruiter ? 'Recruiter' : 'Candidate';
        $role = Role::where('name', $roleName)->first();
        
        if ($role) {
            $user->assignRole($role);
        }
    }

    /**
     * Create a candidate profile with resume.
     *
     * @param User $user
     * @param UploadedFile $resumeFile
     * @return CandidateProfile
     */
    protected function createCandidateProfile(User $user, UploadedFile $resumeFile): CandidateProfile
    {
        $resumePath = $resumeFile->store('resumes', 'public');
        
        return CandidateProfile::create([
            'user_id' => $user->id,
            'resume_path' => $resumePath,
        ]);
    }
}

