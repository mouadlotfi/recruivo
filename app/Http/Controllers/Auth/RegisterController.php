<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\UserRegistrationService;

class RegisterController extends Controller
{
    public function __construct(
        protected UserRegistrationService $registrationService
    ) {}

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $resumeFile = $request->hasFile('resume') ? $request->file('resume') : null;

        $user = $this->registrationService->register($data, $resumeFile);

        // Auto-login the user so they can access the verification notice page
        auth()->login($user);

        // Send email verification notification directly
        // Note: We send it directly to avoid duplicate emails from event listeners
        $user->sendEmailVerificationNotification();

        return redirect()
            ->to(localized_route('verification.notice'))
            ->with('registered', true);
    }
}

