<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Social crawlers (Facebook, LinkedIn, Slack, X) do not execute JavaScript, so
 * metadata set through Vue's <Head> is invisible to them. The shell has to render
 * title, description, canonical and Open Graph tags server-side.
 */
class ShareableMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_job_page_renders_its_own_metadata_server_side(): void
    {
        $company = Company::factory()->create(['name' => 'Aetheris Dynamics', 'slug' => 'aetheris-dynamics']);
        $job = Job::factory()->for($company)->create([
            'title' => 'Senior Backend Engineer',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $html = $this->get("/en/jobs/{$job->id}")->assertOk()->getContent();

        $this->assertStringContainsString(
            '<meta property="og:title" content="Senior Backend Engineer — Recruivo">',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<meta property="og:description" content="[^"]*Aetheris Dynamics[^"]*">/',
            $html
        );
        $this->assertMatchesRegularExpression('/<link rel="canonical" href="https?:\/\/[^"]+\/en\/jobs\/\d+">/', $html);
        $this->assertStringContainsString('<meta name="twitter:card"', $html);
    }

    public function test_a_company_page_is_shareable_by_name_and_tagline(): void
    {
        $company = Company::factory()->create([
            'name' => 'BitForge Software',
            'slug' => 'bitforge-software',
            'tagline' => 'Building reliable systems for growing teams.',
        ]);

        $html = $this->get('/en/companies/'.$company->slug)->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="BitForge Software — Recruivo">', $html);
        $this->assertStringContainsString('content="Building reliable systems for growing teams."', $html);
    }

    public function test_pages_without_their_own_content_fall_back_to_the_site_metadata(): void
    {
        $html = $this->get('/en/jobs')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:type" content="website">', $html);
        $this->assertStringContainsString('Recruivo connects IT professionals', $html);
        $this->assertStringNotContainsString('content=""', $html, 'No metadata tag may render empty.');
    }
}
