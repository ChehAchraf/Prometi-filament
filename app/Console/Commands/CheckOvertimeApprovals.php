<?php

namespace App\Console\Commands;

use App\Models\Pointage;
use App\Models\User;
use App\Notifications\OvertimeApprovalNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckOvertimeApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-overtime-approvals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for unapproved overtime hours and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get pointages with unapproved overtime from the last 7 days
        $pointages = Pointage::where('heures_supplementaires', '>', 0)
            ->where('heures_supplementaires_approuvees', false)
            ->whereDate('date', '>=', Carbon::now()->subDays(7))
            ->whereDate('date', '<=', Carbon::now())
            ->get();
            
        if ($pointages->isEmpty()) {
            $this->info('No unapproved overtime hours found.');
            return 0;
        }
        
        $count = 0;
        
        foreach ($pointages as $pointage) {
            // Find the chef de chantier for this project
            $chefDeChantier = $pointage->project->chefDeChantier;
            
            if (!$chefDeChantier) {
                $this->warn("No chef de chantier found for project {$pointage->project->name}");
                continue;
            }
            
            // Send notification to chef de chantier
            $chefDeChantier->notify(new OvertimeApprovalNotification($pointage));
            $count++;
        }
        
        $this->info("Sent {$count} overtime approval notifications.");
        
        return 0;
    }
}
