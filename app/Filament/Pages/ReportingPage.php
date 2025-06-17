<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ReportingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.reporting';
    protected static ?string $navigationGroup = 'Rapports';
    protected static ?int $navigationSort = -1;
    protected static ?string $title = 'Rapports de pointage';
    protected static ?string $slug = 'reporting';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->role !== 'rh';
    }
    
    public $startDate;
    public $endDate;
    public $projectId;
    public $reportType = 'daily';
    
    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('startDate')
                    ->label('Date de début')
                    ->default(now()->startOfMonth())
                    ->required(),
                
                DatePicker::make('endDate')
                    ->label('Date de fin')
                    ->default(now()->endOfMonth())
                    ->required(),
                
                Select::make('projectId')
                    ->label('Projet')
                    ->options(function() {
                        $options = ['' => 'Tous les projets'];
                        return $options + Project::pluck('name', 'id')->toArray();
                    })
                    ->searchable(),
                

                Select::make('reportType')
                    ->label('Type de rapport')
                    ->options([
                        'daily' => 'Journalier',
                        'weekly' => 'Hebdomadaire',
                        'monthly' => 'Mensuel',
                    ])
                    ->default('daily')
                    ->required(),
            ])
            ->columns(3);
    }
    
    public function generateReport()
    {
        $this->validate();
        
        $this->dispatch('pointage-report-updated', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'projectId' => $this->projectId,
            'reportType' => $this->reportType,
        ]);
        
        Notification::make()
            ->title('Rapport généré avec succès')
            ->success()
            ->send();
    }
    
    // Export functionality has been moved to the Livewire component
}
