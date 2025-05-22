<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PointageReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPointageReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-pointage-reminders {--morning : Send morning reminders} {--evening : Send evening reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to users to complete their pointage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isMorning = $this->option('morning');
        $isEvening = $this->option('evening');
        
        if (!$isMorning && !$isEvening) {
            $this->error('Please specify either --morning or --evening option');
            return 1;
        }
        
        $users = User::where('status', 'actif')
            ->whereIn('role', ['agent', 'chef_de_chantier', 'magasinier'])
            ->get();
            
        $count = 0;
        
        foreach ($users as $user) {
            // Skip users who don't have any active projects
            if ($user->projects()->where('status', 'en_cours')->count() === 0) {
                continue;
            }
            
            $type = $isMorning ? 'arrival' : 'departure';
            $user->notify(new PointageReminderNotification($type));
            $count++;
        }
        
        $this->info("Sent {$count} pointage reminders for " . ($isMorning ? 'arrival' : 'departure'));
        
        return 0;
    }
}
