<?php

use App\Jobs\AutoApproveStaleSubmissions;
use App\Jobs\CloseExpiredTasks;
use App\Jobs\ExpireStaleClaims;
use App\Jobs\SendClaimExpiryWarnings;
use Illuminate\Support\Facades\Schedule;

/*
| Requires one cron entry on the server:
|   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
| Claims are ALSO expired lazily whenever someone tries to claim a task,
| so availability stays correct even if the scheduler is delayed.
*/

Schedule::job(new ExpireStaleClaims)->everyMinute()->withoutOverlapping();
Schedule::job(new SendClaimExpiryWarnings)->everyMinute()->withoutOverlapping();
Schedule::job(new CloseExpiredTasks)->everyFiveMinutes()->withoutOverlapping();
Schedule::job(new AutoApproveStaleSubmissions)->hourly()->withoutOverlapping();
Schedule::command('wallet:reconcile')->dailyAt('03:15');
Schedule::command('sanctum:prune-expired --hours=24')->daily();
