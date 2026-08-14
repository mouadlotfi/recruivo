<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SavedJobsTest extends TestCase
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

    private function candidate(array $attributes = []): User
    {
        $candidate = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
        $candidate->assignRole('Candidate');

        return $candidate;
    }

    private function publishedJob(array $attributes = []): Job
    {
        return Job::factory()->create(array_merge(['status' => JobStatus::Published->value], $attributes));
    }

    public function test_candidate_can_have_saved_jobs(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();

        $candidate->savedJobs()->attach($job);

        $this->assertTrue($candidate->savedJobs->contains($job));
        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_same_job_cannot_be_saved_twice_by_the_same_candidate(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();

        $candidate->savedJobs()->attach($job);

        $this->expectException(QueryException::class);
        $candidate->savedJobs()->attach($job);
    }

    public function test_candidate_can_save_a_published_job(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();

        $this->from('/en/jobs')->actingAs($candidate)
            ->post("/en/candidate/saved-jobs/{$job->id}")
            ->assertRedirect('/en/jobs')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_saving_the_same_job_twice_keeps_a_single_row(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();

        $this->actingAs($candidate)->post("/en/candidate/saved-jobs/{$job->id}");
        $this->actingAs($candidate)->post("/en/candidate/saved-jobs/{$job->id}");

        $this->assertDatabaseCount('saved_jobs', 1);
    }

    public function test_candidate_can_remove_only_their_own_saved_job(): void
    {
        $candidate = $this->candidate();
        $otherCandidate = $this->candidate();
        $job = $this->publishedJob();

        $candidate->savedJobs()->attach($job);
        $otherCandidate->savedJobs()->attach($job);

        $this->from('/en/jobs')->actingAs($candidate)
            ->delete("/en/candidate/saved-jobs/{$job->id}")
            ->assertRedirect('/en/jobs')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $otherCandidate->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_saved_jobs_index_lists_only_the_candidates_saved_published_jobs(): void
    {
        $candidate = $this->candidate();
        $otherCandidate = $this->candidate();
        $savedJob = $this->publishedJob(['title' => 'Saved Role']);
        $draftJob = Job::factory()->create(['status' => JobStatus::Draft->value, 'title' => 'Draft Role']);
        $otherJob = $this->publishedJob(['title' => 'Other Role']);

        $candidate->savedJobs()->attach([$savedJob->id, $draftJob->id]);
        $otherCandidate->savedJobs()->attach($otherJob);

        $response = $this->actingAs($candidate)->get('/en/candidate/saved-jobs');

        $response->assertOk()
            ->assertSee('Saved Role')
            ->assertDontSee('Draft Role')
            ->assertDontSee('Other Role');
    }

    public function test_recruiters_cannot_access_saved_job_routes(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $job = $this->publishedJob();

        $this->actingAs($recruiter)->get('/en/candidate/saved-jobs')->assertForbidden();
        $this->actingAs($recruiter)->post("/en/candidate/saved-jobs/{$job->id}")->assertForbidden();
        $this->actingAs($recruiter)->delete("/en/candidate/saved-jobs/{$job->id}")->assertForbidden();
    }

    public function test_demo_candidate_cannot_save_or_remove_jobs(): void
    {
        $candidate = $this->candidate(['is_demo' => true]);
        $job = $this->publishedJob();

        $this->actingAs($candidate)
            ->post("/en/candidate/saved-jobs/{$job->id}")
            ->assertSessionHasErrors('candidate_action');

        $this->assertDatabaseCount('saved_jobs', 0);

        $candidate->savedJobs()->attach($job);

        $this->actingAs($candidate)
            ->delete("/en/candidate/saved-jobs/{$job->id}")
            ->assertSessionHasErrors('candidate_action');

        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_draft_jobs_cannot_be_saved(): void
    {
        $candidate = $this->candidate();
        $draft = Job::factory()->create(['status' => JobStatus::Draft->value]);

        $this->actingAs($candidate)
            ->post("/en/candidate/saved-jobs/{$draft->id}")
            ->assertNotFound();

        $this->assertDatabaseCount('saved_jobs', 0);
    }

    public function test_candidate_header_links_to_saved_jobs_but_recruiter_header_does_not(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)->get('/en')
            ->assertOk()
            ->assertSee('href="'.localized_route('candidate.saved-jobs.index').'"', false);

        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $this->actingAs($recruiter)->get('/en/recruiter/dashboard')
            ->assertOk()
            ->assertDontSee('href="'.localized_route('candidate.saved-jobs.index').'"', false);
    }

    public function test_saved_jobs_page_has_an_accessible_heading_and_empty_state(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)->get('/en/candidate/saved-jobs')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee(__('jobs.saved_jobs'))
            ->assertSee(__('jobs.no_saved_jobs_yet'))
            ->assertSee('href="'.localized_route('jobs.index').'"', false);
    }

    public function test_saved_jobs_page_renders_saved_job_cards(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob(['title' => 'Bookmarked Role']);
        $candidate->savedJobs()->attach($job);

        $this->actingAs($candidate)->get('/en/candidate/saved-jobs')
            ->assertOk()
            ->assertSee('Bookmarked Role')
            ->assertSee('data-infinite-scroll', false);
    }

    public function test_candidate_job_cards_render_a_bookmark_control_with_an_accessible_44px_target(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();

        $content = $this->actingAs($candidate)->get('/en/jobs')->getContent();

        $this->assertStringContainsString('aria-label="'.__('jobs.save_job').'"', $content);
        $this->assertStringContainsString('relative z-10 inline-flex h-11 w-11', $content);
        $this->assertStringContainsString('action="'.localized_route('candidate.saved-jobs.store', $job).'"', $content);
    }

    public function test_saved_job_cards_render_a_remove_form(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();
        $candidate->savedJobs()->attach($job);

        $content = $this->actingAs($candidate)->get('/en/jobs')->getContent();

        $this->assertStringContainsString('action="'.localized_route('candidate.saved-jobs.destroy', $job).'"', $content);
        $this->assertStringContainsString('name="_method" value="DELETE"', $content);
        $this->assertStringContainsString('aria-label="'.__('jobs.remove_saved_job').'"', $content);
    }

    public function test_guests_do_not_see_a_bookmark_form_on_job_cards(): void
    {
        $this->publishedJob();

        $content = $this->get('/en/jobs')->getContent();

        $this->assertStringNotContainsString('candidate/saved-jobs', $content);
    }

    public function test_job_detail_page_renders_the_bookmark_action_for_candidates(): void
    {
        $candidate = $this->candidate();
        $job = $this->publishedJob();

        $content = $this->actingAs($candidate)->get("/en/jobs/{$job->id}")->getContent();

        $this->assertStringContainsString('action="'.localized_route('candidate.saved-jobs.store', $job).'"', $content);
        $this->assertStringContainsString(__('jobs.save_job'), $content);
    }

    public function test_demo_candidate_sees_a_disabled_bookmark_control_without_a_mutating_form(): void
    {
        $candidate = $this->candidate(['is_demo' => true]);
        $job = $this->publishedJob();

        $content = $this->actingAs($candidate)->get('/en/jobs')->getContent();

        $this->assertStringContainsString(__('jobs.demo_cannot_save_jobs'), $content);
        $this->assertStringContainsString('disabled', $content);
        $this->assertStringNotContainsString('action="'.localized_route('candidate.saved-jobs.store', $job).'"', $content);
    }

    public function test_job_cards_mark_saved_state_from_the_query_without_per_card_queries(): void
    {
        $candidate = $this->candidate();
        $saved = $this->publishedJob(['title' => 'Saved One']);
        $unsaved = $this->publishedJob(['title' => 'Unsaved One']);
        $candidate->savedJobs()->attach($saved);

        $content = $this->actingAs($candidate)->get('/en/jobs')->getContent();

        $this->assertStringContainsString('aria-label="'.__('jobs.remove_saved_job').'"', $content);
        $this->assertStringContainsString('aria-label="'.__('jobs.save_job').'"', $content);
    }

    public function test_job_card_bookmark_control_uses_a_44px_target_and_accessible_label(): void
    {
        $contents = File::get(resource_path('views/components/job-card.blade.php'));

        $this->assertStringContainsString('h-11 w-11', $contents);
        $this->assertStringContainsString('relative z-10', $contents);
        $this->assertStringContainsString('aria-label=', $contents);
        $this->assertStringContainsString('aria-hidden="true"', $contents);
    }
}
