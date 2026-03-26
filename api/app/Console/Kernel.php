<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
        Commands\UpdateUtilities::class,
        Commands\UpdateUtilitiesHW::class,
        Commands\CDOSendSummaryreport::class,
        Commands\MirrorMasters::class,
        Commands\DLMirrorMasters::class,
        Commands\ComputeLeave::class,
        Commands\UpdateDailyTask::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('sbcupdate:utilities')->everyMinute()->withoutOverlapping(10)->runInBackground();
        $schedule->command('sbcupdate:utilitieshw')->everyMinute()->withoutOverlapping(10)->runInBackground();
        $schedule->command('sbcsendemail:emailtransactionsummaryreport')->everyMinute()->withoutOverlapping(10)->runInBackground();
        $schedule->command('sbcupdate:mirrormasters')->everyMinute()->withoutOverlapping(10)->runInBackground();
        $schedule->command('sbcupdate:dlmirrormasters')->everyMinute()->withoutOverlapping(10)->runInBackground();
        $schedule->command('sbcupdate:computeleave')->everyMinute()->withoutOverlapping(10)->runInBackground();
        $schedule->command('sbcupdate:updatedailytask')->everyMinute()->withoutOverlapping(10)->runInBackground();
    }

    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
