<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPointage extends EditRecord
{
    protected static string $resource = PointageResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the current record
        $record = $this->record;
        
        // Add the users to the form data
        $data['users'] = $record->users()->pluck('users.id')->toArray();
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Get the record being edited
        $record = $this->record;
        
        // Get the selected users from the form data
        $users = $this->data['users'] ?? [];
        
        // Sync the users with the pointage record
        $record->users()->sync($users);
        
        // Set a default user_id for backward compatibility if it's not set
        if (!empty($users) && empty($record->user_id)) {
            $record->user_id = $users[0];
            $record->saveQuietly();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
