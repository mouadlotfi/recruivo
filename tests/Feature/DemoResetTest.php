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
        Config::set('database.connections.mysql.database', 'recruivo_production_db');
        Config::set('database.default', 'mysql');

        $exitCode = Artisan::call('demo:reset', ['--force' => true]);

        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('CRITICAL ERROR', $output);
        $this->assertStringContainsString('production database', $output);
    }

    public function test_demo_reset_command_executes_steps_in_demo_environment(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('database.connections.sqlite.database', ':memory:');
        Config::set('database.default', 'sqlite');

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
