<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SystemPreflightCommandTest extends TestCase
{
    public function test_preflight_command_runs_and_prints_summary(): void
    {
        $this->artisan('system:preflight')
            ->expectsOutputToContain('Preflight complete:')
            ->assertExitCode(0);
    }

    public function test_preflight_strict_fails_when_debug_enabled_in_production(): void
    {
        Config::set('app.env', 'production');
        Config::set('app.debug', true);
        Config::set('app.key', 'base64:test-test-test-test-test-test-test=');
        Config::set('session.secure', true);
        Config::set('session.http_only', true);

        $this->artisan('system:preflight --strict')
            ->expectsOutputToContain('FAIL')
            ->assertExitCode(1);
    }
}

