<?php

namespace Tests\Feature;

use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function validMessage(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'message' => 'I cannot replace the resume on my profile - the upload keeps failing.',
        ], $overrides);
    }

    public function test_the_contact_page_renders_in_both_locales_with_metadata(): void
    {
        foreach (['/en/contact', '/fr/contact'] as $url) {
            $response = $this->get($url)->assertOk();

            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Contact/Show', false)
                ->where('labels.title', 'Contact')
                ->where('contact_email', config('mail.contact_address'))
                ->where('labels.send', fn ($value) => is_string($value) && $value !== ''));

            $this->assertStringContainsString(
                '<meta property="og:title" content="Contact — Recruivo">',
                $response->getContent(),
                "{$url} must expose a title to crawlers"
            );
        }
    }

    public function test_a_message_is_emailed_to_the_configured_address(): void
    {
        Notification::fake();

        $this->from('/en/contact')
            ->post('/en/contact', $this->validMessage())
            ->assertRedirect('/en/contact')
            ->assertSessionHas('success');

        Notification::assertSentOnDemand(
            ContactMessageReceived::class,
            function ($notification, $channels, $notifiable) {
                if ($notifiable->routes['mail'] !== config('mail.contact_address')) {
                    return false;
                }

                // Replying from the mailbox must answer the visitor, not the app.
                $mail = $notification->toMail(new AnonymousNotifiable);

                return str_contains(json_encode($mail->replyTo), 'ada@example.com');
            }
        );
    }

    public function test_a_filled_honeypot_is_swallowed_without_sending(): void
    {
        Notification::fake();

        $this->from('/en/contact')
            ->post('/en/contact', $this->validMessage(['contact_website_url' => 'https://spam.example']))
            ->assertRedirect('/en/contact')
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_invalid_messages_are_rejected_without_sending(): void
    {
        Notification::fake();

        $this->from('/en/contact')
            ->post('/en/contact', ['name' => '', 'email' => 'not-an-email', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        Notification::assertNothingSent();
    }
}
