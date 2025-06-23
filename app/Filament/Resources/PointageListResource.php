<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointageListResource\Pages;
use App\Models\Pointage;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PointageListResource extends Resource
{
    protected static ?string $model = Pointage::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Pointage';
    
    protected static ?string $navigationLabel = 'Pointage';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'rh', 'chef_de_chantier', 'chef_de_projet', 'directeur_technique']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
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
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projet')
                    ->relationship('project', 'name'),
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('exportSelection')
                        ->label('Exporter la sélection')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(fn (Collection $records) => self::exportCsv($records)),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Agent')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('project_id')
                            ->relationship('project', 'name')
                            ->label('Projet')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required(),
                        Forms\Components\TimePicker::make('heure_debut')
                            ->label('Heure Début'),
                        Forms\Components\TimePicker::make('heure_fin')
                            ->label('Heure Fin'),
                    ]),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointages::route('/'),
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
} 