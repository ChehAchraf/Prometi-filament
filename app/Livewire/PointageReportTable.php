<?php

namespace App\Livewire;

use App\Models\Pointage;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;

class PointageReportTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    public $startDate;
    public $endDate;
    public $projectId;
    public $reportType = 'daily';
    
    public function mount($startDate = null, $endDate = null, $projectId = null, $reportType = 'daily')
    {
        $this->startDate = $startDate ?? now()->startOfMonth()->format('Y-m-d');
        $this->endDate = $endDate ?? now()->endOfMonth()->format('Y-m-d');
        $this->projectId = $projectId;
        $this->reportType = $reportType;
    }
    
    public function getTableQuery(): Builder
    {
        $query = Pointage::query()
            ->join('projects', 'pointages.project_id', '=', 'projects.id')
            ->select([
                'pointages.id',
                'pointages.date',
                'projects.name as project_name',
                'pointages.heures_travaillees',
                'pointages.heures_supplementaires',
                'pointages.coefficient',
                DB::raw('pointages.heures_travaillees * pointages.coefficient as heures_ponderees'),
                'pointages.status',
            ]);
            
        if ($this->startDate) {
            $query->whereDate('pointages.date', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $query->whereDate('pointages.date', '<=', $this->endDate);
        }
        
        if ($this->projectId) {
            $query->where('pointages.project_id', $this->projectId);
        }
        
        // Group by based on report type
        if ($this->reportType === 'weekly') {
            $query->select([
                DB::raw('pointages.id || "-" || projects.id as id'),
                DB::raw('strftime("%Y%W", pointages.date) as year_week'),
                DB::raw('MIN(pointages.date) as date'),
                'projects.name as project_name',
                DB::raw('SUM(pointages.heures_travaillees) as heures_travaillees'),
                DB::raw('SUM(pointages.heures_supplementaires) as heures_supplementaires'),
                DB::raw('AVG(pointages.coefficient) as coefficient'),
                DB::raw('SUM(pointages.heures_travaillees * pointages.coefficient) as heures_ponderees'),
                DB::raw('MAX(pointages.status) as status'),
            ])
            ->groupBy(DB::raw('strftime("%Y%W", pointages.date)'), 'projects.id');
        } elseif ($this->reportType === 'monthly') {
            $query->select([
                DB::raw('pointages.id || "-" || projects.id as id'),
                DB::raw('strftime("%Y-%m-01", pointages.date) as date'),
                'projects.name as project_name',
                DB::raw('SUM(pointages.heures_travaillees) as heures_travaillees'),
                DB::raw('SUM(pointages.heures_supplementaires) as heures_supplementaires'),
                DB::raw('AVG(pointages.coefficient) as coefficient'),
                DB::raw('SUM(pointages.heures_travaillees * pointages.coefficient) as heures_ponderees'),
                DB::raw('MAX(pointages.status) as status'),
            ])
            ->groupBy(DB::raw('strftime("%Y-%m", pointages.date)'), 'projects.id');
        }
        
        return $query;
    }
    
    public function getTableColumns(): array
    {
        return [
            TextColumn::make('date')
                ->label('Date')
                ->date()
                ->sortable(),
                
            TextColumn::make('project_name')
                ->label('Projet')
                ->searchable(),
                
            TextColumn::make('heures_travaillees')
                ->label('Heures travaillées')
                ->numeric(2)
                ->sortable(),
                
            TextColumn::make('heures_supplementaires')
                ->label('Heures supplémentaires')
                ->numeric(2)
                ->sortable(),
                
            TextColumn::make('coefficient')
                ->label('Coefficient')
                ->numeric(2),
                
            TextColumn::make('heures_ponderees')
                ->label('Heures pondérées')
                ->numeric(2),
                
            TextColumn::make('status')
                ->label('Statut')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'present' => 'success',
                    'absent' => 'danger',
                    'malade' => 'warning',
                    'conge' => 'info',
                    'retard' => 'warning',
                    default => 'gray',
                }),
        ];
    }
    
    public function getTableFilters(): array
    {
        return [
            SelectFilter::make('project_id')
                ->label('Projet')
                ->options(Project::pluck('name', 'id')),
                
            Filter::make('date')
                ->form([
                    DatePicker::make('date_from')
                        ->label('Du'),
                    DatePicker::make('date_to')
                        ->label('Au'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['date_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                        )
                        ->when(
                            $data['date_to'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                        );
                }),
        ];
    }
    
    public function getTableActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function ($record) {
                    // Export a single record
                    return response()->streamDownload(function () use ($record) {
                        // Add BOM for Excel to properly recognize UTF-8
                        echo "\xEF\xBB\xBF";
                        // Headers with proper formatting
                        echo "Date, Projet, Heures Travaillées, Heures Supplémentaires, Coefficient, Heures Pondérées, Statut\n";
                        
                        // Properly escape fields and add spaces after commas
                        $date = $record->date;
                        $project = str_replace(',', ' ', $record->project_name);
                        $heures = $record->heures_travaillees;
                        $heures_supp = $record->heures_supplementaires;
                        $coef = $record->coefficient;
                        $heures_pond = $record->heures_ponderees;
                        $status = $record->status;
                        
                        echo "\"{$date}\", \"{$project}\", {$heures}, {$heures_supp}, {$coef}, {$heures_pond}, \"{$status}\"\n";
                    }, 'pointage-' . $record->date . '.csv');
                }),
        ];
    }
    
    public function getTableHeaderActions(): array
    {
        return [
            \Filament\Tables\Actions\Action::make('exportAll')
                ->label('Exporter tout')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $records = $this->getTableQuery()->get();
                    
                    return response()->streamDownload(function () use ($records) {
                        // Add BOM for Excel to properly recognize UTF-8
                        echo "\xEF\xBB\xBF";
                        // Headers with proper formatting
                        echo "Date, Projet, Heures Travaillées, Heures Supplémentaires, Coefficient, Heures Pondérées, Statut\n";
                        
                        foreach ($records as $record) {
                            // Properly escape fields and add spaces after commas
                            $date = $record->date;
                            $project = str_replace(',', ' ', $record->project_name);
                            $heures = $record->heures_travaillees;
                            $heures_supp = $record->heures_supplementaires;
                            $coef = $record->coefficient;
                            $heures_pond = $record->heures_ponderees;
                            $status = $record->status;
                            
                            echo "\"{$date}\", \"{$project}\", {$heures}, {$heures_supp}, {$coef}, {$heures_pond}, \"{$status}\"\n";
                        }
                    }, 'tous-pointages-' . now()->format('Y-m-d') . '.csv');
                }),
        ];
    }
    
    public function getTableBulkActions(): array
    {
        return [
            BulkAction::make('exportToExcel')
                ->label('Exporter la sélection')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Collection $records) {
                    return response()->streamDownload(function () use ($records) {
                        // Add BOM for Excel to properly recognize UTF-8
                        echo "\xEF\xBB\xBF";
                        // Headers with proper formatting
                        echo "Date, Projet, Heures Travaillées, Heures Supplémentaires, Coefficient, Heures Pondérées, Statut\n";
                        
                        foreach ($records as $record) {
                            // Properly escape fields and add spaces after commas
                            $date = $record->date;
                            $project = str_replace(',', ' ', $record->project_name);
                            $heures = $record->heures_travaillees;
                            $heures_supp = $record->heures_supplementaires;
                            $coef = $record->coefficient;
                            $heures_pond = $record->heures_ponderees;
                            $status = $record->status;
                            
                            echo "\"{$date}\", \"{$project}\", {$heures}, {$heures_supp}, {$coef}, {$heures_pond}, \"{$status}\"\n";
                        }
                    }, 'pointages-selection-' . now()->format('Y-m-d') . '.csv');
                }),
        ];
    }
    
    #[On('pointage-report-updated')]
    public function updateReportParameters($params)
    {
        $this->startDate = $params['startDate'] ?? $this->startDate;
        $this->endDate = $params['endDate'] ?? $this->endDate;
        $this->projectId = $params['projectId'] ?? $this->projectId;
        $this->reportType = $params['reportType'] ?? $this->reportType;
    }
    
    public function render()
    {
        return view('livewire.pointage-report-table');
    }
}
