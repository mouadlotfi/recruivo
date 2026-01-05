<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\EmailChangedNotification;
use App\Notifications\EmailChangeVerificationNotification;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\SignupConfirmationNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_candidate_registration_sends_signup_confirmation_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'account_type' => 'candidate',
            'name' => 'Jane Candidate',
            'email' => 'jane.candidate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
        ]);

        $response->assertCreated();

        $user = User::where('email', 'jane.candidate@example.com')->firstOrFail();

        Notification::assertSentTo($user, SignupConfirmationNotification::class);
    }

    public function test_recruiter_registration_sends_signup_confirmation_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'account_type' => 'company',
            'name' => 'John Recruiter',
            'email' => 'john.recruiter@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'company' => [
                'name' => 'Acme Inc',
                'job_title' => 'Hiring Manager',
                'email' => 'contact@acme.com', // Company email for business
            ],
        ]);

        $response->assertCreated();

        $user = User::where('email', 'john.recruiter@example.com')->firstOrFail();

        Notification::assertSentTo($user, SignupConfirmationNotification::class);
    }

    public function test_user_receives_password_change_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password changed successfully');

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_user_email_change_notifies_old_email_and_sends_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $oldEmail = $user->email;
        $newEmail = 'updated-' . $user->id . '@example.com';

        $response = $this->putJson('/api/profile', [
            'email' => $newEmail,
        ]);

        $response->assertOk();

        Notification::assertSentOnDemand(
            EmailChangedNotification::class,
            function (EmailChangedNotification $notification, array $channels, $notifiable) use ($oldEmail, $user) {
                return in_array('mail', $channels, true)
                    && ($notifiable->routes['mail'] ?? null) === $oldEmail
                    && $notification->toArray($user)['old_email'] === $oldEmail;
            }
        );

        $user->refresh();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $newEmail,
            'email_verified_at' => null,
        ]);

        Notification::assertSentTo($user, EmailChangeVerificationNotification::class);
    }
}

