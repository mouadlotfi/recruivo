<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruiterNoteTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        foreach (['Candidate', 'Recruiter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function makeRecruiter(): User
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }

    public function test_recruiter_can_create_note_template(): void
    {
        $recruiter = $this->makeRecruiter();

        $this->actingAs($recruiter)
            ->post('/en/recruiter/note-templates', [
                'name' => 'Rejection polite',
                'body' => 'Thank you for your application. Unfortunately...',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('recruiter_note_templates', [
            'recruiter_id' => $recruiter->id,
            'name' => 'Rejection polite',
        ]);
    }

    public function test_recruiter_sees_only_their_own_templates(): void
    {
        $recruiter = $this->makeRecruiter();
        $other = $this->makeRecruiter();

        \App\Models\RecruiterNoteTemplate::create([
            'recruiter_id' => $recruiter->id, 'name' => 'Mine', 'body' => 'A',
        ]);
        \App\Models\RecruiterNoteTemplate::create([
            'recruiter_id' => $other->id, 'name' => 'Theirs', 'body' => 'B',
        ]);

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/recruiter/note-templates')
            ->getContent();

        $this->assertStringContainsString('Mine', $html);
        $this->assertStringNotContainsString('Theirs', $html);
    }

    public function test_recruiter_can_update_and_delete_own_template(): void
    {
        $recruiter = $this->makeRecruiter();
        $template = \App\Models\RecruiterNoteTemplate::create([
            'recruiter_id' => $recruiter->id, 'name' => 'Old', 'body' => 'A',
        ]);

        $this->actingAs($recruiter)
            ->put('/en/recruiter/note-templates/'.$template->id, [
                'name' => 'New', 'body' => 'B',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('recruiter_note_templates', ['id' => $template->id, 'name' => 'New']);

        $this->actingAs($recruiter)
            ->delete('/en/recruiter/note-templates/'.$template->id)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('recruiter_note_templates', ['id' => $template->id]);
    }

    public function test_recruiter_cannot_modify_another_recruiters_template(): void
    {
        $recruiter = $this->makeRecruiter();
        $other = $this->makeRecruiter();
        $template = \App\Models\RecruiterNoteTemplate::create([
            'recruiter_id' => $other->id, 'name' => 'Theirs', 'body' => 'B',
        ]);

        $this->actingAs($recruiter)
            ->put('/en/recruiter/note-templates/'.$template->id, ['name' => 'Hacked', 'body' => 'X'])
            ->assertStatus(403);
    }

    public function test_guest_cannot_create_template(): void
    {
        $this->post('/en/recruiter/note-templates', ['name' => 'X', 'body' => 'Y'])
            ->assertRedirect(route('login', ['locale' => 'en']));
    }

    public function test_review_panel_shows_template_picker(): void
    {
        $recruiter = $this->makeRecruiter();
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $job = \App\Models\Job::factory()->for($recruiter->company)->for($recruiter, 'recruiter')->create(['status' => \App\Enums\JobStatus::Published->value]);
        $application = \App\Models\Application::factory()->for($candidate, 'candidate')->for($job)->create();
        \App\Models\RecruiterNoteTemplate::create([
            'recruiter_id' => $recruiter->id, 'name' => 'Polite Rejection', 'body' => 'Thank you for your time.',
        ]);

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->getContent();

        $this->assertStringContainsString('data-note-template-picker', $html);
        $this->assertStringContainsString('Polite Rejection', $html);
    }
}
