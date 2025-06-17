<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin (SuperAdmin)',
                        'rh' => 'RH (Éditeur de compte)',
                        'chef_de_chantier' => 'Chef de chantier (Éditeur de pointage)',
                        'magasinier' => 'Magasinier (Éditeur de pointage)',
                        'chef_de_projet' => 'Chef de projet (Visualisateur)',
                        'directeur_technique' => 'Directeur technique (Visualisateur)',
                        'agent' => 'Agent'
                    ])
                    ->required()
                    ->searchable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('LOWER(role) = ?', ['agent']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'purple',
                        'rh' => 'blue',
                        'chef_de_chantier' => 'green',
                        'magasinier' => 'green',
                        'chef_de_projet' => 'yellow',
                        'directeur_technique' => 'orange',
                        'agent' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'conge' => 'info',
                        'mission' => 'warning',
                        'absent' => 'danger',
                        'malade' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'agent' => 'Agent',
                        'chef_de_projet' => 'Chef de projet',
                        'chef_de_chantier' => 'Chef de chantier',
                        'magasinier' => 'Magasinier',
                        'directeur_technique' => 'Directeur technique',
                        'rh' => 'RH',
                        'admin' => 'Admin'
                    ])
                    ->default('agent')
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereRaw('LOWER(role) = ?', ['agent']))
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
