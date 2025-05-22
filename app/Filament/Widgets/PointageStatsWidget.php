<?php

namespace App\Filament\Widgets;

use App\Models\Pointage;
use App\Models\Project;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PointageStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Get the current month's data
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        // Total hours worked this month
        $totalHours = Pointage::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('heures_travaillees');
            
        // Total overtime hours this month
        $totalOvertime = Pointage::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('heures_supplementaires');
            
        // Total approved overtime hours
        $approvedOvertime = Pointage::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('heures_supplementaires_approuvees', true)
            ->sum('heures_supplementaires');
            
        // Active projects count
        $activeProjects = Project::where('status', 'en_cours')->count();
        
        // Active employees count
        $activeEmployees = User::where('status', 'actif')->where('role', 'agent')->count();
        
        return [
            Stat::make('Heures travaillées ce mois', number_format($totalHours, 2))
                ->description('Total des heures travaillées')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),
                
            Stat::make('Heures supplémentaires', number_format($totalOvertime, 2))
                ->description(number_format($approvedOvertime, 2) . ' heures approuvées')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('warning'),
                
            Stat::make('Projets actifs', $activeProjects)
                ->description($activeEmployees . ' employés actifs')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
