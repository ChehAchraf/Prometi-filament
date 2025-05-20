<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePointage extends CreateRecord
{
    protected static string $resource = PointageResource::class;
    
    // This method runs before the record is created
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get the selected users from the form data
        $users = $data['users'] ?? [];
        
        // Set the user_id to the first selected user for backward compatibility
        if (!empty($users)) {
            $data['user_id'] = $users[0];
        } else {
            // If no users are selected, set a default user_id (1 for admin or any default user)
            $data['user_id'] = 1; // You might want to change this to a valid user ID in your system
        }
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Get the created record
        $record = $this->record;
        
        // Get the selected users from the form data
        $users = $this->data['users'] ?? [];
        
        // Sync the users with the pointage record
        if (!empty($users)) {
            $record->users()->sync($users);
        }
    }
}
