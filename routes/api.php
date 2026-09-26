<?php

use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyLogoController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Recruiter\DashboardController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Candidate\ResumeController;
use App\Http\Middleware\RefuseDemoMutations;
use App\Models\User;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Public readiness probe and the deploy pipeline's only gate: never echo exception
// details here.
Route::get('/health', function () {
    $checks = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'checks' => [],
    ];

    try {
        DB::connection()->getPdo();
        $checks['checks']['database'] = 'ok';
    } catch (Throwable) {
        $checks['checks']['database'] = 'failed';
        $checks['status'] = 'unhealthy';
    }

    try {
        $migrator = app(Migrator::class);
        $pending = array_diff(
            array_keys($migrator->getMigrationFiles(
                array_merge($migrator->paths(), [database_path('migrations')])
            )),
            $migrator->getRepository()->getRan(),
        );

        $checks['checks']['migrations'] = $pending === [] ? 'ok' : count($pending).' pending';

        if ($pending !== []) {
            $checks['status'] = 'unhealthy';
        }
    } catch (Throwable) {
        $checks['checks']['migrations'] = 'failed';
        $checks['status'] = 'unhealthy';
    }

    try {
        Cache::store()->get('health_check');
        $checks['checks']['cache'] = 'ok';
    } catch (Throwable) {
        $checks['checks']['cache'] = 'failed';
        $checks['status'] = 'unhealthy';
    }

    try {
        // Encrypt/decrypt round trip: an empty or malformed APP_KEY breaks every
        // session cookie, signed URL and queued payload while the app still boots.
        Crypt::decryptString(Crypt::encryptString('health_check'));
        $checks['checks']['app_key'] = 'ok';
    } catch (Throwable) {
        $checks['checks']['app_key'] = 'failed';
        $checks['status'] = 'unhealthy';
    }

    try {
        Storage::disk('private')->exists('health_check');
        $checks['checks']['storage'] = 'ok';
    } catch (Throwable) {
        $checks['checks']['storage'] = 'failed';
        $checks['status'] = 'unhealthy';
    }

    $statusCode = $checks['status'] === 'healthy' ? 200 : 503;

    return response()->json($checks, $statusCode);
})->name('api.health');

// Company logos are <img>-sourced by every listing page and cached by Caddy, so
// they stay outside the API throttle.
Route::get('/companies/{slug}/logo', [CompanyLogoController::class, 'show'])->name('api.companies.logo');

Route::middleware('throttle:api')->group(function () {
    Route::get('/jobs', [JobController::class, 'index'])->name('api.jobs.index');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('api.jobs.show')->whereNumber('job');
    Route::get('/companies', [CompanyController::class, 'index'])->name('api.companies.index');
    Route::get('/companies/{company:slug}', [CompanyController::class, 'show'])->name('api.companies.show');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('api.search.suggestions');
});

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:auth-register');

Route::post('/email/verification-notification', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
    ]);

    $user = User::where('email', $request->email)->first();

    if ($user && ! $user->hasVerifiedEmail()) {
        $user->sendEmailVerificationNotification();
    }

    return response()->json([
        'message' => 'If the account exists and needs verification, a link has been sent.',
    ]);
})->middleware('throttle:verification-email')->name('verification.resend');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::post('/jobs/{jobId}/apply', [ApplicationController::class, 'store'])->whereNumber('jobId')
        ->middleware('throttle:job-apply')
        ->name('api.jobs.apply');
    Route::middleware(['role:Recruiter', RefuseDemoMutations::class])->group(function () {
        Route::get('/recruiter/jobs', [App\Http\Controllers\Api\Recruiter\JobController::class, 'index']);
        Route::post('/recruiter/jobs', [App\Http\Controllers\Api\Recruiter\JobController::class, 'store']);
        Route::get('/recruiter/jobs/{job}', [App\Http\Controllers\Api\Recruiter\JobController::class, 'show'])->whereNumber('job');
        Route::put('/recruiter/jobs/{job}', [App\Http\Controllers\Api\Recruiter\JobController::class, 'update'])->whereNumber('job');
        Route::delete('/recruiter/jobs/{job}', [App\Http\Controllers\Api\Recruiter\JobController::class, 'destroy'])->whereNumber('job');
        Route::post('/recruiter/jobs/{job}/toggle', [App\Http\Controllers\Api\Recruiter\JobController::class, 'toggle'])->whereNumber('job');

        Route::get('/recruiter/dashboard', [DashboardController::class, 'index']);

        Route::get('/recruiter/company-profile', [CompanyProfileController::class, 'show']);
        Route::put('/recruiter/company-profile', [CompanyProfileController::class, 'update']);

        Route::get('/recruiter/jobs/{job}/applications', [App\Http\Controllers\Api\Recruiter\ApplicationController::class, 'index'])->whereNumber('job');
        Route::patch('/recruiter/applications/{application}', [App\Http\Controllers\Api\Recruiter\ApplicationController::class, 'update']);
        Route::get('/recruiter/applications/{application}/resume', [App\Http\Controllers\Api\Recruiter\ApplicationController::class, 'downloadResume']);
    });

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    Route::middleware('role:Candidate')->group(function () {
        Route::get('/candidate/applications', [App\Http\Controllers\Api\Candidate\ApplicationController::class, 'index']);
        Route::get('/candidate/resume', [ResumeController::class, 'view'])
            ->name('api.candidate.resume');
    });

    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    });
});
