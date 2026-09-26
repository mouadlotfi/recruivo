<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_switch_rebuilds_the_route_and_preserves_the_query_string(): void
    {
        $this->get('/en/search?q=design/en'); // records the previous URL

        $this->get('/locale/fr')
            // `/` inside a query value is re-encoded to %2F by the request
            // parser — the key contract is that the locale swap never touches
            // the query string (previously str_replace corrupted it).
            ->assertRedirect('/fr/search?q=design%2Fen');
    }

    public function test_switch_keeps_route_parameters_intact(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->for($company)->create([
            'title' => 'Cloud Engineer',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->get("/en/jobs/{$job->id}"); // records the previous URL

        $this->get('/locale/fr')
            ->assertRedirect("/fr/jobs/{$job->id}");
    }

    public function test_switch_without_a_previous_page_prefixes_the_locale(): void
    {
        $this->get('/locale/fr')
            ->assertRedirect('/fr');
    }

    public function test_switch_rejects_unsupported_locales(): void
    {
        $this->get('/locale/de')->assertStatus(400);
    }
}
