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

class PointageResource extends Resource
{
    protected static ?string $model = Pointage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
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
                Select::make('users')
                    ->relationship('users', 'name', function ($query) {
                        return $query->where('role', 'agent');
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->label('Agents'),
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TimePicker::make('heure_debut')
                    ->required(),
                TimePicker::make('heure_fin')
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
                            return "ID: {$user->id} - {$user->name}";
                        })->implode('\n');
                    })
                    ->html()
                    ->searchable(),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->searchable(),
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
                    ->searchable(),
                TextColumn::make('heures_supplementaires')
                    ->label('Heures Supplémentaires')
                    ->searchable(),
                Tables\Columns\IconColumn::make('heures_supplementaires_approuvees')
                    ->label('Approuvées')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Status')
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
            'index' => Pages\ListPointages::route('/'),
            'create' => Pages\CreatePointage::route('/create'),
            'edit' => Pages\EditPointage::route('/{record}/edit'),
        ];
    }
}
