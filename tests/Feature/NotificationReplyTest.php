<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Notifications\ApplicationStatusUpdatedNotification;
use App\Notifications\NewApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Outgoing mail comes from noreply@recruivo.work, and that domain has no MX
 * record - so anything a recipient sends back to it is refused at the far end,
 * silently, with neither side learning that it happened.
 *
 * Every notification whose copy invites a reply therefore has to name a real
 * mailbox, which is what these pin. The contact form has always done this; the
 * application notifications did not, which meant a recruiter replying to a
 * candidate wrote into a void.
 */
class NotificationReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recruiter_can_reply_to_an_application(): void
    {
        $application = Application::factory()->create();

        $mail = (new NewApplicationNotification($application))
            ->toMail($application->job->recruiter);

        $this->assertStringContainsString(
            $application->candidate->email,
            json_encode($mail->replyTo),
            'Replying to an application notification must reach the candidate.'
        );
    }

    public function test_a_candidate_can_reply_to_a_status_update(): void
    {
        $application = Application::factory()->create();
        $application->job->company->update(['email' => 'careers@example.com']);

        $mail = (new ApplicationStatusUpdatedNotification($application))
            ->toMail($application->candidate);

        $this->assertStringContainsString(
            'careers@example.com',
            json_encode($mail->replyTo),
            'The status mail invites a reply, so it has to reach the company.'
        );
    }

    public function test_a_company_without_a_contact_address_still_sends(): void
    {
        // Not every company publishes one. The mail still has to go out; it just
        // has no reply address, which is the behaviour that existed before.
        $application = Application::factory()->create();
        $application->job->company->update(['email' => null]);

        $mail = (new ApplicationStatusUpdatedNotification($application))
            ->toMail($application->candidate);

        $this->assertEmpty($mail->replyTo);
    }
}
