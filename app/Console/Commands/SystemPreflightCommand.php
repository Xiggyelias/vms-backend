<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class SystemPreflightCommand extends Command
{
    protected $signature = 'system:preflight {--strict : Exit non-zero when any check fails}';

    protected $description = 'Run production readiness preflight checks';

    public function handle(): int
    {
        $checks = [
            [
                'name' => 'APP_ENV is production',
                'ok' => config('app.env') === 'production',
                'fix' => 'Set APP_ENV=production',
            ],
            [
                'name' => 'APP_DEBUG disabled',
                'ok' => config('app.debug') === false,
                'fix' => 'Set APP_DEBUG=false',
            ],
            [
                'name' => 'APP_KEY configured',
                'ok' => !empty((string) config('app.key')),
                'fix' => 'Generate and set APP_KEY',
            ],
            [
                'name' => 'Secure session cookies enabled',
                'ok' => (bool) config('session.secure') === true,
                'fix' => 'Set SESSION_SECURE_COOKIE=true',
            ],
            [
                'name' => 'HTTP-only session cookies enabled',
                'ok' => (bool) config('session.http_only') === true,
                'fix' => 'Set SESSION_HTTP_ONLY=true',
            ],
            [
                'name' => 'Legacy frontend mutations disabled',
                'ok' => !filter_var((string) env('ALLOW_LEGACY_MUTATIONS', 'false'), FILTER_VALIDATE_BOOL),
                'fix' => 'Set ALLOW_LEGACY_MUTATIONS=false',
            ],
            [
                'name' => 'Critical backend compatibility routes exist',
                'ok' => $this->criticalRoutesExist(),
                'fix' => 'Ensure route registration for submit, draft, onboarding, and status mutation endpoints',
            ],
        ];

        $failed = 0;
        foreach ($checks as $check) {
            if ($check['ok']) {
                $this->line("<info>PASS</info> {$check['name']}");
            } else {
                $failed++;
                $this->line("<error>FAIL</error> {$check['name']} :: {$check['fix']}");
            }
        }

        $this->newLine();
        if ($failed === 0) {
            $this->info('Preflight complete: all checks passed.');
            return Command::SUCCESS;
        }

        $this->warn("Preflight complete: {$failed} check(s) failed.");
        if ($this->option('strict')) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function criticalRoutesExist(): bool
    {
        $required = [
            'registration.submit',
            'registration.submit.vehicle-form',
            'drafts.save',
            'auth.google.token',
            'auth.google.finalize-role',
            'vehicles.status.admin.update',
        ];

        foreach ($required as $name) {
            if (!Route::has($name)) {
                return false;
            }
        }

        return true;
    }
}

