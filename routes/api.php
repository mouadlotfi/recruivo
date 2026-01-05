<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyLogoController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    $checks = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'checks' => [],
    ];

    try {
        DB::connection()->getPdo();
        $checks['checks']['database'] = 'ok';
    } catch (\Exception $e) {
        $checks['checks']['database'] = 'failed';
        $checks['status'] = 'unhealthy';
    }

    try {
        Cache::store()->get('health_check');
        $checks['checks']['cache'] = 'ok';
    } catch (\Exception $e) {
        $checks['checks']['cache'] = 'failed';
    }

    $statusCode = $checks['status'] === 'healthy' ? 200 : 503;
    return response()->json($checks, $statusCode);
})->name('api.health');

Route::get('/jobs', [JobController::class, 'index'])->name('api.jobs.index');
Route::get('/jobs/{job}', [JobController::class, 'show'])->name('api.jobs.show');
Route::get('/companies', [CompanyController::class, 'index'])->name('api.companies.index');
Route::get('/companies/{company:slug}', [CompanyController::class, 'show'])->name('api.companies.show');
Route::get('/companies/{slug}/logo', [CompanyLogoController::class, 'show'])->name('api.companies.logo');
Route::get('/search/suggestions', [\App\Http\Controllers\Api\SearchController::class, 'suggestions'])->name('api.search.suggestions');

// Authentication routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Email verification routes (no authentication required)
Route::post('/email/verification-notification', function (Request $request) {
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();
    
    if ($user && !$user->hasVerifiedEmail()) {
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent']);
    }

    return response()->json(['message' => 'Email already verified or not found'], 400);
})->middleware('throttle:6,1')->name('verification.resend');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Candidate routes
    Route::post('/jobs/{jobId}/apply', [ApplicationController::class, 'store'])
        ->name('api.jobs.apply');

    // Recruiter routes
    Route::middleware('role:Recruiter')->group(function () {
        // Job management
        Route::get('/recruiter/jobs', [\App\Http\Controllers\Api\Recruiter\JobController::class, 'index']);
        Route::post('/recruiter/jobs', [\App\Http\Controllers\Api\Recruiter\JobController::class, 'store']);
        Route::get('/recruiter/jobs/{job}', [\App\Http\Controllers\Api\Recruiter\JobController::class, 'show']);
        Route::put('/recruiter/jobs/{job}', [\App\Http\Controllers\Api\Recruiter\JobController::class, 'update']);
        Route::delete('/recruiter/jobs/{job}', [\App\Http\Controllers\Api\Recruiter\JobController::class, 'destroy']);
        Route::post('/recruiter/jobs/{job}/toggle', [\App\Http\Controllers\Api\Recruiter\JobController::class, 'toggle']);

        // Dashboard metrics
        Route::get('/recruiter/dashboard', [\App\Http\Controllers\Api\Recruiter\DashboardController::class, 'index']);

        // Company profile management
        Route::get('/recruiter/company-profile', [CompanyProfileController::class, 'show']);
        Route::put('/recruiter/company-profile', [CompanyProfileController::class, 'update']);

        // Application management
        Route::get('/recruiter/jobs/{job}/applications', [\App\Http\Controllers\Api\Recruiter\ApplicationController::class, 'index']);
        Route::patch('/recruiter/applications/{application}', [\App\Http\Controllers\Api\Recruiter\ApplicationController::class, 'update']);
        Route::get('/recruiter/applications/{application}/resume', [\App\Http\Controllers\Api\Recruiter\ApplicationController::class, 'downloadResume']);
    });

    // Profile management (for all authenticated users)
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Candidate routes
    Route::middleware('role:Candidate')->group(function () {
        Route::get('/candidate/applications', [\App\Http\Controllers\Api\Candidate\ApplicationController::class, 'index']);
    });

    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
    });
});
