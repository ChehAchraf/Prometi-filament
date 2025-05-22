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
        // Send morning reminders at 8:00 AM on weekdays
        $schedule->command('app:send-pointage-reminders --morning')
                 ->weekdays()
                 ->at('08:00')
                 ->timezone('Europe/Paris');
        
        // Send evening reminders at 5:00 PM on weekdays
        $schedule->command('app:send-pointage-reminders --evening')
                 ->weekdays()
                 ->at('17:00')
                 ->timezone('Europe/Paris');
                 
        // Check for unapproved overtime hours and send notifications
        $schedule->command('app:check-overtime-approvals')
                 ->dailyAt('22:00')
                 ->timezone('Europe/Paris');
                 
        // Check for unjustified absences
        $schedule->command('app:check-absences')
                 ->dailyAt('23:00')
                 ->timezone('Europe/Paris');
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
