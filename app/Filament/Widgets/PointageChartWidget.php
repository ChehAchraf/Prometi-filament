<?php

namespace App\Filament\Widgets;

use App\Models\Pointage;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class PointageChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Heures travaillées par projet';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '400px';

    protected function getData(): array
    {
        $projects = Project::all();
        
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        
        $datasets = [];
        $labels = [];
        
        // Get all dates in the current month
        $period = Carbon::parse($startOfMonth)->daysUntil($endOfMonth);
        
        foreach ($period as $date) {
            $labels[] = $date->format('d/m');
        }
        
        $colors = [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 206, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
            'rgb(255, 159, 64)',
        ];
        
        $colorIndex = 0;
        
        foreach ($projects as $project) {
            $data = [];
            
            foreach ($period as $date) {
                $hoursWorked = Pointage::where('project_id', $project->id)
                    ->whereDate('date', $date)
                    ->sum('heures_travaillees');
                
                $data[] = $hoursWorked;
            }
            
            $datasets[] = [
                'label' => $project->name,
                'data' => $data,
                'borderColor' => $colors[$colorIndex % count($colors)],
                'backgroundColor' => $colors[$colorIndex % count($colors)] . '0.2)',
            ];
            
            $colorIndex++;
        }
        
        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
