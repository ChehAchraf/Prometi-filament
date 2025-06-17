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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Projet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
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
                    }),
                TextColumn::make('heure_debut')
                    ->label('Heure début')
                    ->time()
                    ->visible(fn ($record) => $record && in_array($record->status, ['present', 'retard'])),
                TextColumn::make('heure_fin')
                    ->label('Heure fin')
                    ->time()
                    ->visible(fn ($record) => $record && in_array($record->status, ['present', 'retard'])),
                TextColumn::make('heures_travaillees')
                    ->label('Heures travaillées')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('heures_supplementaires')
                    ->label('Heures Supp.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('commentaire')
                    ->label('Commentaire')
                    ->limit(50),
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
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('date_to')
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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