<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PointageChartWidget;
use App\Filament\Widgets\PointageStatsWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationGroup = 'Tableaux de bord';
    
    protected static ?int $navigationSort = -2;
    
    protected static ?string $title = 'Tableau de bord';
    
    protected static ?string $slug = 'dashboard';
    
    protected static string $view = 'filament.pages.dashboard';
    
    public function getWidgets(): array
    {
        return [
            PointageStatsWidget::class,
            PointageChartWidget::class,
        ];
    }
}
