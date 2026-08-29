<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\EmailChangeVerificationNotification;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\SignupConfirmationNotification;
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
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
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
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
            'phone' => '1234567890',
            'company' => [
                'name' => 'Acme Inc',
                'location' => 'Casablanca, Morocco',
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
            'password' => bcrypt('CurrentPass123!'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'CurrentPass123!',
            'password' => 'ChangedPass123!',
            'password_confirmation' => 'ChangedPass123!',
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
        $newEmail = 'updated-'.$user->id.'@example.com';

        $response = $this->putJson('/api/profile', [
            'email' => $newEmail,
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $oldEmail,
            'pending_email' => $newEmail,
        ]);

        Notification::assertSentOnDemand(
            EmailChangeVerificationNotification::class,
            fn (EmailChangeVerificationNotification $notification, array $channels, $notifiable) => in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $newEmail
        );
    }
}
