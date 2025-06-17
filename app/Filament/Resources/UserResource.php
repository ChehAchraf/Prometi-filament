<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Gestion des Utilisateurs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                TextInput::make('password')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                TextInput::make('city')
                    ->required()
                    ->maxLength(255),
                TextInput::make('matricule')
                    ->required()
                    ->unique()
                    ->maxLength(255),
                TextInput::make('fonction')
                    ->required()
                    ->maxLength(255),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin (SuperAdmin)',
                        'rh' => 'RH (Éditeur de compte)',
                        'chef_de_chantier' => 'Chef de chantier (Éditeur de pointage)',
                        'magasinier' => 'Magasinier (Éditeur de pointage)',
                        'chef_de_projet' => 'Chef de projet (Visualisateur)',
                        'directeur_technique' => 'Directeur technique (Visualisateur)',
                        'colaborateur' => 'Colaborateur'
                    ])
                    ->required()
                    ->searchable(),
                Select::make('status')
                    ->options([
                        'actif' => 'Actif',
                        'conge' => 'En congé',
                        'mission' => 'En mission',
                        'absent' => 'Absent',
                        'malade' => 'Malade',
                        'accident_de_travail' => 'Accident de travail',
                        'retraite' => 'Retraité',
                    ])
                    ->default('actif')
                    ->required(),
                Select::make('projects')
                    ->relationship('projects', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->label('Projets assignés'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('role')
                    ->searchable(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
