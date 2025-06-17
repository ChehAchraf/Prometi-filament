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
        return parent::getEloquentQuery()->with('users');
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
                                    ->whereIn('users.role', ['agent', 'colaborateur'])
                                    ->get();
                                    
                                foreach ($agents as $agent) {
                                    $agentItems[] = [
                                        'agent_id' => $agent->id,
                                        'agent_name' => $agent->name,
                                        'status' => 'present',
                                        'heure_debut' => $defaultStartTime,
                                        'heure_fin' => $defaultEndTime,
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
                    ->required(),
                Toggle::make('is_jour_ferie')
                    ->label('Jour férié')
                    ->helperText('Cochez si c\'est un jour férié'),
                Forms\Components\Section::make('Agents du projet')
                    ->description('Gérer le pointage pour tous les agents du projet')
                    ->schema([
                        Forms\Components\Repeater::make('agents_table')
                            ->label('Tableau des agents')
                            ->schema([
                                Forms\Components\Hidden::make('agent_id'),
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
                                    ->visible(fn (callable $get) => $get('status') !== 'absent' && $get('status') !== 'conge' && $get('status') !== 'malade')
                                    ->columnSpan(1),
                                TimePicker::make('heure_fin')
                                    ->label('Heure de fin')
                                    ->seconds(false)
                                    ->required()
                                    ->visible(fn (callable $get) => $get('status') !== 'absent' && $get('status') !== 'conge' && $get('status') !== 'malade')
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
                Forms\Components\Section::make('Commentaire général')
                    ->schema([
                        Forms\Components\Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->maxLength(500),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('users.name')
                    ->label('Agents')
                    ->formatStateUsing(function ($state, $record) {
                        $users = $record->users;
                        if ($users->isEmpty()) {
                            return '-';
                        }
                        
                        return $users->map(function ($user) {
                            return "{$user->name}";
                        })->implode(', ');
                    })
                    ->html()
                    ->searchable(),
                TextColumn::make('project.name')
                    ->label('Projet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_jour_ferie')
                    ->label('Jour Férié')
                    ->boolean(),
                Tables\Columns\TextColumn::make('heure_debut')
                    ->label('Heure Début')
                    ->time()
                    ->searchable(),
                Tables\Columns\TextColumn::make('heure_fin')
                    ->label('Heure Fin')
                    ->time()
                    ->searchable(),
                TextColumn::make('heures_travaillees')
                    ->label('Heures Travaillées')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('heures_supplementaires')
                    ->label('Heures Supp.')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('coefficient')
                    ->label('Coef.')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\IconColumn::make('heures_supplementaires_approuvees')
                    ->label('HS Approuvées')
                    ->boolean(),
                TextColumn::make('weighted_hours')
                    ->label('Heures Pondérées')
                    ->getStateUsing(fn (Pointage $record): float => $record->getWeightedHoursAttribute())
                    ->numeric(2)
                    ->sortable(),
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
                    })
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projet')
                    ->relationship('project', 'name'),
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
} 