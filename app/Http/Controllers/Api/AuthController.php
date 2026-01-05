<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsAuthenticatedUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\UserRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use FormatsAuthenticatedUsers;

    public function __construct(
        protected UserRegistrationService $registrationService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Find user by personal email (both candidates and recruiters use personal email for login)
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'These credentials do not match our records.',
                'errors' => [
                    'email' => ['These credentials do not match our records.']
                ]
            ], 422);
        }

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email address before logging in.',
                'errors' => [
                    'email' => ['Please verify your email address before logging in.']
                ],
                'requires_verification' => true,
                'email' => $user->email, // Always use personal email for verification
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $this->formatUserResponse($user),
            'token' => $token,
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $resumeFile = $request->hasFile('resume') ? $request->file('resume') : null;

        $user = $this->registrationService->register($data, $resumeFile);

        // Send custom signup confirmation notification
        $user->notify(new \App\Notifications\SignupConfirmationNotification($user));

        return response()->json([
            'message' => 'Registration successful! Please check your email to verify your account.',
            'email' => $user->email,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

}
