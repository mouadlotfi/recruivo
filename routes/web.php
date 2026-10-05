<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Candidate\DashboardController;
use App\Http\Controllers\Candidate\ResumeController;
use App\Http\Controllers\Candidate\SavedJobController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Recruiter\NoteTemplateController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\RefuseDemoMutations;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $supported = config('locales.supported', ['en', 'fr']);
    $cookieLocale = $request->cookie('locale');
    if ($cookieLocale && in_array($cookieLocale, $supported, true)) {
        return redirect('/'.$cookieLocale);
    }

    $preferred = $request->getPreferredLanguage($supported);

    return redirect('/'.($preferred ?: config('locales.default', 'en')));
});

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Crawler endpoints. Deliberately not localized: a crawler asks for one canonical
// path, and the sitemap enumerates every locale itself.
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

Route::prefix('{locale}')->where(['locale' => 'en|fr'])->middleware(SetLocale::class)->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show')->whereNumber('job');
    Route::get('/search', [JobController::class, 'search'])->name('search');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{slug}', [CompanyController::class, 'show'])->name('companies.show');

    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');

    Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
    Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('contact.store');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:auth-login');
        Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:auth-register');

        Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.request');
        Route::post('/reset-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:password-reset')->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('/reset-password-submit', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset')->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/email/verify', function (Request $request) {
            if ($request->user()->hasVerifiedEmail()) {
                return redirect(localized_route($request->user()->hasRole('Recruiter') ? 'recruiter.dashboard' : 'home'));
            }

            return Inertia::render('Auth/VerifyEmail', [
                'email' => $request->user()->email,
                'labels' => [
                    'title' => __('auth.verify_email_short'),
                    'description' => __('auth.verify_email_desc_short'),
                    'resend' => __('auth.resend_verification'),
                    'logout' => __('auth.log_out'),
                    'message' => session('message'),
                ],
                'message' => session('message'),
            ]);
        })->name('verification.notice');

        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('message', 'Verification link sent!');
        })->middleware('throttle:verification-email')->name('verification.send');
    });

    Route::get('/email/verify/{id}/{hash}', function (Request $request, $locale, $id, $hash) {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(localized_route('login'))->with('info', 'Email already verified.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        auth()->login($user);

        if ($user->hasRole('Recruiter')) {
            return redirect(localized_route('recruiter.dashboard'))->with('verified', true);
        } elseif ($user->hasRole('Candidate')) {
            return redirect(localized_route('home'))->with('verified', true);
        } elseif ($user->hasRole('Admin')) {
            return redirect(localized_route('admin.dashboard'))->with('verified', true);
        }

        return redirect(localized_route('home'))->with('verified', true);
    })->middleware(['signed'])->name('verification.verify');

    Route::get('/profile/email/verify/{id}/{hash}', [ProfileController::class, 'verifyEmailChange'])
        ->middleware('signed')
        ->name('profile.email.verify');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::post('/notifications/{notification}', [NotificationController::class, 'open'])
            ->whereUuid('notification')
            ->name('notifications.open');

        Route::middleware('role:Candidate')->prefix('candidate')->name('candidate.')->group(function () {
            Route::get('/applications', [App\Http\Controllers\Candidate\ApplicationController::class, 'index'])->name('applications');
            Route::patch('/applications/{application}/withdraw', [App\Http\Controllers\Candidate\ApplicationController::class, 'withdraw'])
                ->name('applications.withdraw');
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/resume', [ResumeController::class, 'view'])->name('resume.view');
            Route::get('/saved-jobs', [SavedJobController::class, 'index'])->name('saved-jobs.index');
            Route::post('/saved-jobs/{job}', [SavedJobController::class, 'store'])->name('saved-jobs.store')->whereNumber('job');
            Route::delete('/saved-jobs/{job}', [SavedJobController::class, 'destroy'])->name('saved-jobs.destroy')->whereNumber('job');
        });

        Route::middleware(['role:Recruiter', RefuseDemoMutations::class])->prefix('recruiter')->name('recruiter.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Recruiter\DashboardController::class, 'index'])->name('dashboard');

            Route::resource('jobs', App\Http\Controllers\Recruiter\JobController::class);
            Route::post('/jobs/{job}/toggle', [App\Http\Controllers\Recruiter\JobController::class, 'toggle'])->name('jobs.toggle')->whereNumber('job');
            Route::get('/jobs/{job}/applications', [App\Http\Controllers\Recruiter\ApplicationController::class, 'index'])->name('jobs.applications')->whereNumber('job');
            Route::patch('/applications/{application}', [App\Http\Controllers\Recruiter\ApplicationController::class, 'update'])->name('applications.update');
            Route::get('/applications/{application}/resume', [App\Http\Controllers\Recruiter\ApplicationController::class, 'downloadResume'])->name('applications.resume');
            Route::get('/note-templates', [NoteTemplateController::class, 'index'])->name('note-templates.index');
            Route::post('/note-templates', [NoteTemplateController::class, 'store'])->name('note-templates.store');
            Route::put('/note-templates/{template}', [NoteTemplateController::class, 'update'])->name('note-templates.update');
            Route::delete('/note-templates/{template}', [NoteTemplateController::class, 'destroy'])->name('note-templates.destroy');
        });

        Route::middleware(['role:Admin', RefuseDemoMutations::class])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/jobs', [App\Http\Controllers\Admin\JobController::class, 'index'])->name('jobs');
            Route::get('/users', [UserController::class, 'index'])->name('users');
            Route::get('/users/{user}/candidate', [UserController::class, 'candidateProfile'])->name('users.candidate');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])->whereNumber('job')
            ->name('jobs.apply')
            ->middleware(['role:Candidate', 'throttle:job-apply']);

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::get('/candidate/profile-preview', [ProfileController::class, 'preview'])
            ->name('candidate.profile-preview')
            ->middleware('role:Candidate');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
        Route::put('/profile/email/request', [ProfileController::class, 'requestEmailChange'])
            ->middleware('throttle:verification-email')
            ->name('profile.email.request');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Candidate quick preferences (pre-verification popup: unverified candidates must be able to save/skip)
    Route::middleware(['auth', 'role:Candidate'])->prefix('candidate')->name('candidate.')->group(function () {
        Route::post('/preferences', [ProfileController::class, 'saveQuickPreferences'])->name('preferences.quick');
    });
});
Route::fallback(function () {
    $path = request()->path();

    // The API has no locales, so an unmatched /api/... address is simply not
    // found. Sending it to a language-prefixed URL invents a page that cannot
    // exist, and turns one wrong answer into two requests.
    if (preg_match('/^api($|\/)/', $path)) {
        abort(404);
    }

    if (! preg_match('/^(en|fr)($|\/)/', $path)) {
        return redirect('/en/'.ltrim($path, '/'));
    }

    abort(404);
});
