<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_uses_the_request_locale(): void
    {
        $user = User::factory()->create(['email' => 'candidate@example.com']);
        app()->setLocale('fr');

        $url = (new ResetPassword('test-token'))->toMail($user)->actionUrl;

        $this->assertStringStartsWith(url('/fr/reset-password/test-token'), $url);
        $this->assertStringContainsString('email='.urlencode($user->email), $url);
    }
}
