<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('notifications:verification-reminders')
            ->dailyAt('23:59')
            ->timezone('UTC')
            ->withoutOverlapping();
        // Review kit email, once per public business, max 50 a run (on the live host cron calls it directly)
        $schedule->command('notifications:review-kit')
            ->dailyAt('04:00')
            ->timezone('UTC')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
