<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointageManageResource\Pages;
use App\Models\Pointage;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class PointageManageResource extends Resource
{
    protected static ?string $model = Pointage::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Pointage';
    
    protected static ?string $navigationLabel = 'Create Pointage';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'rh', 'chef_de_chantier', 'chef_de_projet', 'directeur_technique']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $project = Project::find($state);
                            if ($project) {
                                $agentItems = [];
                                $defaultStartTime = '08:00';
                                $defaultEndTime = '17:00';
                                
                                $agents = $project->users()
                                    ->where(function ($query) {
                                        $query->whereRaw('LOWER(users.role) = ?', ['agent'])
                                              ->orWhereRaw('LOWER(users.role) = ?', ['colaborateur']);
                                    })
                                    ->get();
                                    
                                foreach ($agents as $agent) {
                                    $agentItems[] = [
                                        'agent_id' => $agent->id,
                                        'agent_name' => $agent->name,
                                        'status' => 'present',
                                        'heure_debut' => $defaultStartTime,
                                        'heure_fin' => $defaultEndTime,
                                        'total_hours' => 8,
                                        'commentaire' => '',
                                    ];
                                }
                                
                                $set('agents_table', $agentItems);
                            } else {
                                $set('agents_table', []);
                            }
                        }
                    }),
                DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Toggle::make('is_jour_ferie')
                    ->label('Jour férié')
                    ->helperText('Cochez si c\'est un jour férié'),
                Forms\Components\Section::make('Agents du projet')
                    ->description('Gérer le pointage pour tous les agents du projet')
                    ->schema([
                        Forms\Components\Repeater::make('agents_table')
                            ->label('Tableau des agents')
                            ->schema([
                                Forms\Components\Hidden::make('agent_id')
                                    ->required(),
                                Forms\Components\TextInput::make('agent_name')
                                    ->label('Agent')
                                    ->disabled()
                                    ->columnSpan(1),
                                Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'present' => 'Présent',
                                        'absent' => 'Absent',
                                        'malade' => 'Malade',
                                        'conge' => 'Congé',
                                        'retard' => 'Retard',
                                    ])
                                    ->default('present')
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),
                                TimePicker::make('heure_debut')
                                    ->label('Heure de début')
                                    ->seconds(false)
                                    ->required()
                                    ->visible(fn (callable $get) => in_array($get('status'), ['present', 'retard']))
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        static::calculateTotalHours($state, $get('heure_fin'), $set);
                                    })
                                    ->columnSpan(1),
                                TimePicker::make('heure_fin')
                                    ->label('Heure de fin')
                                    ->seconds(false)
                                    ->required()
                                    ->visible(fn (callable $get) => in_array($get('status'), ['present', 'retard']))
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        static::calculateTotalHours($get('heure_debut'), $state, $set);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('total_hours')
                                    ->label('Heures totales')
                                    ->disabled()
                                    ->numeric()
                                    ->default(8)
                                    ->visible(fn (callable $get) => in_array($get('status'), ['present', 'retard']))
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('commentaire')
                                    ->label('Commentaire')
                                    ->maxLength(255)
                                    ->columnSpan(1),
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'md' => 3,
                                'lg' => 5,
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['agent_name'] ?? 'Agent')
                            ->collapsible(false)
                            ->defaultItems(0)
                            ->live(),
                    ]),
                Toggle::make('heures_supplementaires_approuvees')
                    ->label('Approuver automatiquement les heures supplémentaires')
                    ->helperText('Cochez pour approuver automatiquement les heures supplémentaires')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Agent')
                    ->formatStateUsing(function ($state, $record) {
                        $user = $record->user;
                        if (!$user) {
                            return '-';
                        }
                        
                        return $user->name;
                    })
                    ->html()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Projet')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_jour_ferie')
                    ->label('Jour Férié')
                    ->boolean(),
                TextColumn::make('heure_debut')
                    ->label('Heure Début')
                    ->time()
                    ->searchable(),
                TextColumn::make('heure_fin')
                    ->label('Heure Fin')
                    ->time()
                    ->searchable(),
                TextColumn::make('heures_travaillees')
                    ->label('Heures Travaillées')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('heures_supplementaires')
                    ->label('Heures Supp.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('heures_supplementaires_approuvees')
                    ->label('HS Approuvées')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('coefficient')
                    ->label('Coef.')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('weighted_hours')
                    ->label('Heures Pondérées')
                    ->getStateUsing(fn (Pointage $record): float => $record->getWeightedHoursAttribute())
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'malade' => 'Malade',
                        'conge' => 'Congé',
                        'retard' => 'Retard',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'malade' => 'warning',
                        'conge' => 'info',
                        'retard' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projet')
                    ->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'malade' => 'Malade',
                        'conge' => 'Congé',
                        'retard' => 'Retard',
                    ]),
                Tables\Filters\SelectFilter::make('date')
                    ->label('Période')
                    ->options([
                        'today' => 'Aujourd\'hui',
                        'week' => 'Cette semaine',
                        'month' => 'Ce mois-ci',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $query->when($data['value'] ?? null, function (Builder $query, $value) {
                            if ($value === 'today') {
                                $query->whereDate('date', today());
                            } elseif ($value === 'week') {
                                $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
                            } elseif ($value === 'month') {
                                $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
                            }
                        });
                        return $query;
                    }),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exporter')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return self::exportCsv($records);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('approve_overtime')
                    ->label('Approuver HS')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Pointage $record) => $record->approveOvertime(true))
                    ->visible(fn (Pointage $record): bool => $record->heures_supplementaires > 0 && !$record->heures_supplementaires_approuvees),
                Action::make('disapprove_overtime')
                    ->label('Désapprouver HS')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Pointage $record) => $record->approveOvertime(false))
                    ->visible(fn (Pointage $record): bool => $record->heures_supplementaires > 0 && $record->heures_supplementaires_approuvees),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('exportSelection')
                        ->label('Exporter la sélection')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(fn (Collection $records) => self::exportCsv($records)),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointages::route('/'),
            'create' => Pages\CreatePointage::route('/create'),
        ];
    }

    public static function exportCsv(Collection $records)
    {
        return response()->streamDownload(function () use ($records) {
            // Add BOM for Excel to properly recognize UTF-8
            echo "\xEF\xBB\xBF";
            // Headers with proper formatting
            echo "Date;Agent;Projet;Heures Travaillées;Heures Supplémentaires;Coefficient;Heures Pondérées;Statut\n";

            foreach ($records as $record) {
                // Properly escape fields and use semicolon as delimiter
                $date = $record->date;
                $agent = str_replace(';', ' ', $record->user->name ?? 'N/A');
                $project = str_replace(';', ' ', $record->project->name ?? 'N/A');
                $heures = $record->heures_travaillees;
                $heures_supp = $record->heures_supplementaires;
                $coef = $record->coefficient;
                $heures_pond = $record->getWeightedHoursAttribute();
                $status = $record->status;

                echo "\"{$date}\";\"{$agent}\";\"{$project}\";\"{$heures}\";\"{$heures_supp}\";\"{$coef}\";\"{$heures_pond}\";\"{$status}\"\n";
            }
        }, 'pointages-' . now()->format('Y-m-d') . '.csv');
    }

    protected static function calculateTotalHours($startTime, $endTime, callable $set): void
    {
        if (!$startTime || !$endTime) {
            $set('total_hours', 0);
            return;
        }

        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);

        // If end time is before start time, assume it's the next day
        if ($end->lt($start)) {
            $end->addDay();
        }

        // Calculate total hours
        $totalHours = $end->floatDiffInHours($start);

        // Define lunch break
        $lunchStart = \Carbon\Carbon::parse($start->format('Y-m-d') . ' 12:00');
        $lunchEnd = \Carbon\Carbon::parse($start->format('Y-m-d') . ' 13:00');

        // Check if the work period overlaps with the lunch break
        if ($start < $lunchEnd && $end > $lunchStart) {
            // Calculate the overlap duration
            $overlapStart = $start->max($lunchStart);
            $overlapEnd = $end->min($lunchEnd);
            $overlapDuration = $overlapEnd->floatDiffInHours($overlapStart);

            // Deduct the overlap from total hours, ensuring not to deduct more than 1 hour
            $totalHours -= min($overlapDuration, 1.0);
        }

        $set('total_hours', round(max(0, $totalHours), 2));
    }
} 