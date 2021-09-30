<?php

namespace Rappasoft\LaravelAuthenticationLog\Commands;

use Illuminate\Console\Command;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class PurgeAuthenticationLogCommand extends Command
{
    public $signature = 'authentication-log:purge';

    public $description = 'Purge all authentication logs older than the configurable amount of days.';

    public function handle(): void
    {
        $this->comment('Clearing authentication log...');

        $deleted = AuthenticationLog::where('login_at', '<', now()->subDays(config('authentication-log.purge'))->format('Y-m-d H:i:s'))->delete();

        $this->info($deleted . ' authentication logs cleared.');
    }
}
