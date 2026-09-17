<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The crawler-facing surface: robots.txt, the sitemap, and the structured data
 * that puts a posting into Google's jobs experience.
 *
 * This is the channel that actually brings a new job board its candidates, and
 * it is invisible unless it is right - a page can look perfect to a person and
 * be worthless to a crawler. So the shape is pinned here rather than left to be
 * noticed in search console months later.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_points_crawlers_at_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_robots_keeps_crawlers_out_of_the_areas_that_need_a_session(): void
    {
        $body = $this->get('/robots.txt')->getContent();

        foreach (['en', 'fr'] as $locale) {
            foreach (['admin', 'candidate', 'recruiter', 'profile'] as $path) {
                $this->assertStringContainsString(
                    "Disallow: /{$locale}/{$path}",
                    $body,
                    "The [/{$locale}/{$path}] area is not disallowed."
                );
            }
        }
    }

    public function test_robots_refuses_every_crawler_in_the_demo_environment(): void
    {
        // The Demo runs the same image, so it would otherwise publish a second
        // copy of the same listings advertising vacancies nobody is hiring for,
        // and compete with the real site for its own content.
        App::detectEnvironment(fn () => 'demo');

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
    }

    public function test_the_sitemap_lists_published_jobs_in_every_enabled_locale(): void
    {
        $job = Job::factory()->create(['status' => JobStatus::Published]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringContainsString('/en/jobs/'.$job->id, $xml);
        $this->assertStringContainsString('/fr/jobs/'.$job->id, $xml);
    }

    public function test_the_sitemap_leaves_out_a_job_nobody_can_see(): void
    {
        $draft = Job::factory()->create(['status' => JobStatus::Draft]);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/en/jobs/'.$draft->id, $xml);
    }

    public function test_the_sitemap_lists_companies_and_posts_too(): void
    {
        $company = Company::factory()->create();
        $post = Post::factory()->published()->create();

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/en/companies/'.$company->slug, $xml);
        $this->assertStringContainsString('/en/posts/'.$post->getTranslation('slug', 'en'), $xml);
    }

    public function test_the_sitemap_is_well_formed_xml(): void
    {
        Job::factory()->create(['status' => JobStatus::Published]);
        Company::factory()->create();
        Post::factory()->published()->create();

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'The sitemap must parse as XML.');
    }

    public function test_a_job_page_carries_jobposting_structured_data(): void
    {
        $job = Job::factory()->create([
            'status' => JobStatus::Published,
            'title' => 'Senior Laravel Engineer',
        ]);

        $data = $this->structuredDataFrom($this->get('/en/jobs/'.$job->id));

        $this->assertSame('JobPosting', $data['@type']);
        $this->assertSame('Senior Laravel Engineer', $data['title']);
        $this->assertSame($job->company->name, $data['hiringOrganization']['name']);
        $this->assertSame((string) $job->id, $data['identifier']['value']);
        $this->assertSame($job->published_at->toIso8601String(), $data['datePosted']);
        $this->assertTrue($data['directApply'], 'Applications happen on this site, which is what direct apply means.');
    }

    public function test_a_remote_job_is_marked_as_telecommute(): void
    {
        $job = Job::factory()->create([
            'status' => JobStatus::Published,
            'remote_type' => 'remote',
        ]);

        $this->assertSame(
            'TELECOMMUTE',
            $this->structuredDataFrom($this->get('/en/jobs/'.$job->id))['jobLocationType']
        );
    }

    public function test_the_structured_data_makes_no_claim_about_salary(): void
    {
        // salary_min and salary_max are bare integers: no currency, no period.
        // Any baseSalary would therefore assert a currency the employer never
        // chose, and Google republishes salary as fact.
        $job = Job::factory()->create(['status' => JobStatus::Published]);

        $this->assertArrayNotHasKey(
            'baseSalary',
            $this->structuredDataFrom($this->get('/en/jobs/'.$job->id))
        );
    }

    public function test_the_structured_data_survives_a_title_that_looks_like_markup(): void
    {
        // Titles and descriptions are recruiter-controlled. An unescaped closing
        // script tag would end the JSON-LD block early and let everything after
        // it be parsed as markup.
        $job = Job::factory()->create([
            'status' => JobStatus::Published,
            'title' => 'Engineer </script><img src=x onerror=alert(1)>',
        ]);

        $block = $this->jsonLdBlocks($this->get('/en/jobs/'.$job->id))[0];

        $this->assertStringNotContainsString('</script', $block);
        $this->assertSame(
            'Engineer </script><img src=x onerror=alert(1)>',
            $this->structuredDataFrom($this->get('/en/jobs/'.$job->id))['title'],
            'The title must survive the round trip through the JSON-LD block.'
        );
    }

    public function test_a_job_nobody_can_see_is_not_announced_to_crawlers(): void
    {
        $draft = Job::factory()->create(['status' => JobStatus::Draft]);

        $response = $this->get('/en/jobs/'.$draft->id);

        $response->assertNotFound();
        $this->assertStringNotContainsString('JobPosting', $response->getContent());
    }

    /**
     * The raw JSON-LD blocks on a page.
     *
     * @return array<int, string>
     */
    private function jsonLdBlocks(TestResponse $response): array
    {
        preg_match_all(
            '/<script type="application\/ld\+json">(.*?)<\/script>/s',
            $response->getContent(),
            $matches
        );

        $this->assertNotEmpty($matches[1], 'The page carries no JSON-LD block.');

        return $matches[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredDataFrom(TestResponse $response): array
    {
        $blocks = $this->jsonLdBlocks($response);

        $this->assertCount(1, $blocks, 'The page carries more than one JSON-LD block.');

        $decoded = json_decode($blocks[0], true);

        $this->assertIsArray($decoded, 'The JSON-LD block is not valid JSON.');

        return $decoded;
    }
}
