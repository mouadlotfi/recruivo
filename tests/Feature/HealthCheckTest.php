<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_reports_healthy_when_schema_is_current(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.migrations', 'ok')
            ->assertJsonPath('checks.app_key', 'ok');
    }

    public function test_health_check_fails_when_migrations_are_pending(): void
    {
        DB::table('migrations')->where('id', DB::table('migrations')->max('id'))->delete();

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.migrations', '1 pending');
    }

    public function test_health_check_does_not_leak_internal_error_details(): void
    {
        Cache::shouldReceive('store')
            ->andThrow(new RuntimeException('Redis connection to tcp://redis:6379 failed, password=hunter2'));

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('checks.cache', 'failed')
            ->assertDontSee('tcp://redis:6379')
            ->assertDontSee('hunter2');
    }
}
