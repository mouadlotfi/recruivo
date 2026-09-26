<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The public JSON endpoints had no throttling at all: the `api` middleware group
 * only ever existed in the (unused) App\Http\Kernel, so `throttle:api` never
 * applied to a real request.
 */
class ApiThrottlingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A deliberately low limit keeps the test cheap: what is under test is the
     * route -> limiter wiring, which is the same wiring the production 'api'
     * limiter (120/min) uses.
     */
    private function limitApiTo(int $perMinute): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute($perMinute)->by($request->ip()));
    }

    public function test_public_api_endpoints_are_rate_limited(): void
    {
        $this->limitApiTo(2);

        $this->getJson('/api/jobs')->assertOk();
        $this->getJson('/api/jobs')->assertOk();
        $this->getJson('/api/jobs')->assertStatus(429);

        // The limiter is keyed per client, not per endpoint.
        $this->getJson('/api/companies')->assertStatus(429);
        $this->getJson('/api/search/suggestions?q=engineer')->assertStatus(429);
    }

    public function test_health_check_is_never_throttled(): void
    {
        // The container healthcheck probes this endpoint every few seconds; a 429
        // would mark a healthy container as failed and roll the deployment back.
        $this->limitApiTo(1);

        $this->getJson('/api/health')->assertOk();
        $this->getJson('/api/health')->assertOk();
        $this->getJson('/api/health')->assertOk();
    }

    public function test_company_logos_are_never_throttled(): void
    {
        // Every listing page <img>-loads one logo per company.
        $this->limitApiTo(1);

        $this->getJson('/api/companies/missing-company/logo')->assertStatus(404);
        $this->getJson('/api/companies/missing-company/logo')->assertStatus(404);
        $this->getJson('/api/companies/missing-company/logo')->assertStatus(404);
    }
}
