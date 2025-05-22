<?php

namespace App\Console\Commands;

use App\Models\Pointage;
use App\Models\Project;
use App\Models\User;
use App\Notifications\AbsenceAlertNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-absences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for unjustified absences and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check only for weekdays
        $today = Carbon::now();
        if ($today->isWeekend()) {
            $this->info('Today is weekend, skipping absence check.');
            return 0;
        }
        
        // Get all active users with role 'agent'
        $users = User::where('status', 'actif')
            ->where('role', 'agent')
            ->get();
            
        if ($users->isEmpty()) {
            $this->info('No active agents found.');
            return 0;
        }
        
        $count = 0;
        
        foreach ($users as $user) {
            // Skip users who don't have any active projects
            if ($user->projects()->where('status', 'en_cours')->count() === 0) {
                continue;
            }
            
            // Check if user has any pointage for today
            $hasPointage = Pointage::whereHas('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->whereDate('date', $today)
                ->exists();
                
            if (!$hasPointage) {
                // Get the user's chef de chantier from their projects
                $projects = $user->projects()->where('status', 'en_cours')->get();
                
                foreach ($projects as $project) {
                    $chefDeChantier = $project->chefDeChantier;
                    
                    if ($chefDeChantier) {
                        // Send notification to chef de chantier
                        $chefDeChantier->notify(new AbsenceAlertNotification($user, $today));
                        $count++;
                    }
                }
                
                // Also notify RH
                $rhUsers = User::where('role', 'rh')->get();
                foreach ($rhUsers as $rh) {
                    $rh->notify(new AbsenceAlertNotification($user, $today));
                    $count++;
                }
            }
        }
        
        $this->info("Sent {$count} absence alert notifications.");
        
        return 0;
    }
}
