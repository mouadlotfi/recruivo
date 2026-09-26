<?php

namespace Tests\Feature;

use App\Console\Commands\DemoReset;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class DemoResetTest extends TestCase
{
    public function test_demo_reset_command_refuses_to_run_in_production_environment(): void
    {
        App::detectEnvironment(fn () => 'production');

        $exitCode = Artisan::call('demo:reset', ['--force' => true]);

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('CRITICAL ERROR', $output);
        $this->assertStringContainsString('production', $output);
    }

    public function test_demo_reset_refuses_to_run_against_production_named_database(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('database.connections.pgsql.database', 'recruivo_production_db');
        Config::set('database.default', 'pgsql');

        $exitCode = Artisan::call('demo:reset', ['--force' => true]);

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('CRITICAL ERROR', $output);
        $this->assertStringContainsString('recruivo_production_db', $output);
    }

    /**
     * The production database is named `recruivo_db`, which no denylist of
     * production-ish words matches - so the guard must be the other way round.
     */
    public function test_demo_reset_refuses_the_production_database_name(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('database.connections.pgsql.database', 'recruivo_db');
        Config::set('database.default', 'pgsql');

        $command = $this->createPartialMock(DemoReset::class, ['call', 'option']);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new NullOutput));
        $command->method('option')->willReturn(true);
        // The guard has to return before anything destructive: no console command
        // may be invoked at all.
        $command->expects($this->never())->method('call');

        $this->assertSame(1, $command->handle());
    }

    public function test_demo_reset_command_executes_steps_in_demo_environment(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('database.connections.pgsql.database', 'recruivo_test');
        Config::set('database.default', 'pgsql');

        $command = $this->createPartialMock(DemoReset::class, ['call', 'option']);
        $outputStyle = new OutputStyle(new ArrayInput([]), new NullOutput);
        $command->setOutput($outputStyle);
        $command->method('option')->with('force')->willReturn(true);
        $command->expects($this->exactly(3))
            ->method('call')
            ->willReturnCallback(function (string $cmd) {
                $this->assertContains($cmd, ['cache:clear', 'view:clear', 'migrate:fresh']);

                return 0;
            });

        $this->assertSame(0, $command->handle());
    }
}
