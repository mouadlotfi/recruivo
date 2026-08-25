<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\UserRegistrationService;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function __construct(
        protected UserRegistrationService $registrationService
    ) {}

    public function showRegistrationForm()
    {
        return Inertia::render('Auth/Register', [
            'labels' => [
                'title' => __('auth.register_title'),
                'subtitle' => __('auth.register_subtitle'),
                'account_type' => __('auth.account_type'),
                'candidate' => __('auth.account_type_candidate'),
                'candidate_desc' => __('auth.account_type_candidate_desc'),
                'company' => __('auth.account_type_company'),
                'company_desc' => __('auth.account_type_company_desc'),
                'name' => __('auth.full_name'),
                'name_placeholder' => __('auth.full_name_placeholder'),
                'email' => __('auth.email'),
                'email_placeholder' => __('auth.email_placeholder'),
                'phone' => __('auth.phone_number_optional'),
                'password' => __('auth.password'),
                'password_placeholder' => __('auth.password_create_placeholder'),
                'password_confirmation' => __('auth.confirm_password_label'),
                'password_confirmation_placeholder' => __('auth.password_confirm_placeholder'),
                'resume' => __('auth.resume_optional'),
                'resume_help' => __('auth.resume_help_text'),
                'company_name' => __('auth.company_name_label'),
                'company_name_placeholder' => __('auth.company_name_placeholder'),
                'company_tagline' => __('auth.company_tagline_optional'),
                'company_tagline_placeholder' => __('auth.company_tagline_placeholder'),
                'company_location' => __('auth.company_location'),
                'company_location_placeholder' => __('auth.company_location_placeholder'),
                'website' => __('auth.website_url_optional'),
                'website_placeholder' => __('auth.website_url_placeholder'),
                'linkedin' => __('auth.linkedin_url_optional'),
                'linkedin_placeholder' => __('auth.linkedin_url_placeholder'),
                'submit' => __('auth.create_account_button'),
                'already_member' => __('auth.already_member'),
                'sign_in' => __('auth.sign_in'),
                'toggle_password_visibility' => __('common.toggle_password_visibility'),
            ],
            'old' => [
                'account_type' => old('account_type', 'candidate'),
                'name' => old('name', ''),
                'email' => old('email', ''),
                'phone' => old('phone', ''),
                'company' => old('company', []),
            ],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $resumeFile = $request->hasFile('resume') ? $request->file('resume') : null;

        $user = $this->registrationService->register($data, $resumeFile);

        // Authenticate user so they can access the verification notice route.
        auth()->login($user);

        if ($user->hasRole('Candidate')) {
            session()->put('show_preferences_picker', true);
        }

        // Sent directly to prevent duplicate dispatch from Registered event listeners.
        $user->sendEmailVerificationNotification();
        return redirect()
            ->to(localized_route('verification.notice'))
            ->with('registered', true);
    }
}

