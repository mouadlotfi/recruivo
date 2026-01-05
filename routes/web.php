<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Redirect root to default locale
Route::get('/', function () {
    return redirect('/en');
});

// Catch any route without locale prefix and redirect to /en version
Route::fallback(function () {
    $path = request()->path();
    
    // Only redirect if the path doesn't already start with a locale
    if (!preg_match('/^(en|fr)\//', $path)) {
        return redirect('/en/' . $path);
    }
    
    abort(404);
});

// Locale switching route (redirects to same page in new locale)
Route::get('/locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

// ALL routes are localized with language prefix
Route::prefix('{locale}')->where(['locale' => 'en|fr'])->middleware(\App\Http\Middleware\SetLocale::class)->group(function () {
    
    // Public routes
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
    Route::get('/search', [JobController::class, 'search'])->name('search');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{slug}', [CompanyController::class, 'show'])->name('companies.show');
    
    // Blog/Post routes
    Route::get('/posts', [\App\Http\Controllers\PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{slug}', [\App\Http\Controllers\PostController::class, 'show'])->name('posts.show');

    // Guest routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
        Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register']);
        
        // Password Reset Routes
        Route::get('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.request');
        Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink'])->name('password.email');
        Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('/reset-password-submit', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
    });

    // Email Verification Routes
    Route::middleware('auth')->group(function () {
        Route::get('/email/verify', function () {
            return view('auth.verify-email');
        })->name('verification.notice');

        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('message', 'Verification link sent!');
        })->middleware(['throttle:6,1'])->name('verification.send');
    });

    Route::get('/email/verify/{id}/{hash}', function (Request $request, $locale, $id, $hash) {
        $user = \App\Models\User::findOrFail($id);
        
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }
        
        if ($user->hasVerifiedEmail()) {
            return redirect(localized_route('login'))->with('info', 'Email already verified.');
        }
        
        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));
        
        // Login the user automatically after verification
        auth()->login($user);
        
        // Redirect based on user role
        if ($user->hasRole('Recruiter')) {
            return redirect(localized_route('recruiter.dashboard'))->with('verified', true);
        } elseif ($user->hasRole('Candidate')) {
            return redirect(localized_route('home'))->with('verified', true);
        } elseif ($user->hasRole('Admin')) {
            return redirect(localized_route('admin.dashboard'))->with('verified', true);
        }
        
        return redirect(localized_route('home'))->with('verified', true);
    })->middleware(['signed'])->name('verification.verify');

    // Authenticated routes
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        
        // Candidate routes
        Route::middleware('role:Candidate')->prefix('candidate')->name('candidate.')->group(function () {
            Route::get('/applications', [\App\Http\Controllers\Candidate\ApplicationController::class, 'index'])->name('applications');
            Route::get('/dashboard', [\App\Http\Controllers\Candidate\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/resume', [\App\Http\Controllers\Candidate\ResumeController::class, 'view'])->name('resume.view');
        });

        // Recruiter routes
        Route::middleware('role:Recruiter')->prefix('recruiter')->name('recruiter.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Recruiter\DashboardController::class, 'index'])->name('dashboard');
            Route::resource('jobs', \App\Http\Controllers\Recruiter\JobController::class);
            Route::post('/jobs/{job}/toggle', [\App\Http\Controllers\Recruiter\JobController::class, 'toggle'])->name('jobs.toggle');
            Route::get('/jobs/{job}/applications', [\App\Http\Controllers\Recruiter\ApplicationController::class, 'index'])->name('jobs.applications');
            Route::patch('/applications/{application}', [\App\Http\Controllers\Recruiter\ApplicationController::class, 'update'])->name('applications.update');
            Route::get('/applications/{application}/resume', [\App\Http\Controllers\Recruiter\ApplicationController::class, 'downloadResume'])->name('applications.resume');
        });

        // Admin routes
        Route::middleware('role:Admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users');
            Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
        });

        // Job application (candidates only)
        Route::post('/jobs/{job}/apply', [\App\Http\Controllers\ApplicationController::class, 'store'])
            ->name('jobs.apply')
            ->middleware('role:Candidate');

        // Profile routes
        Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.password');
        Route::put('/profile/email/request', [\App\Http\Controllers\ProfileController::class, 'requestEmailChange'])->name('profile.email.request');
        Route::get('/profile/email/verify/{id}/{hash}', [\App\Http\Controllers\ProfileController::class, 'verifyEmailChange'])->name('profile.email.verify');
        Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});
