<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointageResource\Pages;
use App\Filament\Resources\PointageResource\RelationManagers;
use App\Models\Pointage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class PointageResource extends Resource
{
    protected static ?string $model = Pointage::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Gestion du Pointage';
    
    // Override the create method to handle the many-to-many relationship
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('users');
    }

    // No longer needed as we'll use Filament's hooks instead
    
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
                            // Get all agents attached to the selected project
                            $project = \App\Models\Project::find($state);
                            if ($project) {
                                $agentIds = $project->users()
                                    ->where('role', 'agent')
                                    ->pluck('users.id')
                                    ->toArray();
                                
                                // Set the agents to the form
                                $set('users', $agentIds);
                            }
                        }
                    }),
                Select::make('users')
                    ->relationship('users', 'name', function ($query) {
                        return $query->where('role', 'agent');
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->label('Agents'),
                DatePicker::make('date')
                    ->required(),
                Toggle::make('is_jour_ferie')
                    ->label('Jour férié')
                    ->helperText('Cochez si c\'est un jour férié'),
                TimePicker::make('heure_debut')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('heure_fin')
                    ->seconds(false)
                    ->required(),
                TextInput::make('heures_travaillees')
                    ->label('Heures Travaillées')
                    ->numeric()
                    ->disabled()
                    ->helperText('Calculé automatiquement'),
                TextInput::make('heures_supplementaires')
                    ->label('Heures Supplémentaires')
                    ->numeric()
                    ->disabled()
                    ->helperText('Calculé automatiquement'),
                TextInput::make('coefficient')
                    ->label('Coefficient')
                    ->numeric()
                    ->disabled()
                    ->helperText('Calculé automatiquement selon les plages horaires'),
                Toggle::make('heures_supplementaires_approuvees')
                    ->label('Approuver les heures supplémentaires')
                    ->helperText('Cochez pour approuver les heures supplémentaires'),
                Select::make('status')
                    ->options([
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'malade' => 'Malade',
                        'conge' => 'Congé',
                        'retard' => 'Retard',
                    ])
                    ->required()
                    ->searchable(),
                Forms\Components\Textarea::make('commentaire')
                    ->label('Commentaire')
                    ->maxLength(500),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'malade' => 'Malade',
                        'conge' => 'Congé',
                        'retard' => 'Retard',
                    ]),
                Tables\Filters\TernaryFilter::make('is_jour_ferie')
                    ->label('Jour férié'),
                Tables\Filters\TernaryFilter::make('heures_supplementaires_approuvees')
                    ->label('HS Approuvées'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approveOvertime')
                    ->label('Approuver HS')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Pointage $record) {
                        $record->approveOvertime(true);
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->hidden(fn (Pointage $record) => $record->heures_supplementaires_approuvees || $record->heures_supplementaires <= 0),
                Tables\Actions\Action::make('rejectOvertime')
                    ->label('Refuser HS')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (Pointage $record) {
                        $record->approveOvertime(false);
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->hidden(fn (Pointage $record) => !$record->heures_supplementaires_approuvees || $record->heures_supplementaires <= 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('approveBulkOvertime')
                        ->label('Approuver HS en masse')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records) {
                            $records->each(function (Pointage $record) {
                                if ($record->heures_supplementaires > 0) {
                                    $record->approveOvertime(true);
                                    $record->save();
                                }
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('exportToExcel')
                        ->label('Exporter en Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Collection $records) {
                            return response()->streamDownload(function () use ($records) {
                                echo "Date,Projet,Agents,Heures Travaillées,Heures Supplémentaires,Coefficient,Heures Pondérées,Statut\n";
                                
                                foreach ($records as $record) {
                                    $agents = $record->users->pluck('name')->implode(', ');
                                    echo "{$record->date},{$record->project->name},\"{$agents}\",{$record->heures_travaillees},{$record->heures_supplementaires},{$record->coefficient},{$record->getWeightedHoursAttribute()},{$record->status}\n";
                                }
                            }, 'pointages-' . now()->format('Y-m-d') . '.csv');
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointages::route('/'),
            'create' => Pages\CreatePointage::route('/create'),
            'edit' => Pages\EditPointage::route('/{record}/edit'),
        ];
    }
}
