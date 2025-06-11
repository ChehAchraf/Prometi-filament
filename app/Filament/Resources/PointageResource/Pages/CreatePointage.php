<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use App\Models\Pointage;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePointage extends CreateRecord
{
    protected static string $resource = PointageResource::class;
    
    // This method runs before the record is created
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract agents data from table
        $agentsTable = $data['agents_table'] ?? [];
        
        // For backward compatibility, set user_id to the first agent if available
        if (!empty($agentsTable)) {
            $data['user_id'] = $agentsTable[0]['agent_id'];
        } else {
            $data['user_id'] = 1; // Default user ID
        }
        
        // Make sure heures_supplementaires_approuvees is handled
        if (!isset($data['heures_supplementaires_approuvees'])) {
            $data['heures_supplementaires_approuvees'] = false;
        }
        
        // Use the first present agent's time data for the main pointage record
        $presentAgent = null;
        foreach ($agentsTable as $agent) {
            if ($agent['status'] === 'present' || $agent['status'] === 'retard') {
                $presentAgent = $agent;
                break;
            }
        }
        
        if ($presentAgent) {
            $data['status'] = $presentAgent['status'];
            $data['heure_debut'] = $presentAgent['heure_debut'];
            $data['heure_fin'] = $presentAgent['heure_fin'];
        } else {
            // Default values if no present agent
            $data['status'] = 'absent';
            $data['heure_debut'] = null;
            $data['heure_fin'] = null;
        }
        
        // Remove fields that aren't in the Pointage model
        unset($data['agents_table']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Get the created record
        $record = $this->record;
        
        // Get the agents data from the form
        $agentsTable = $this->data['agents_table'] ?? [];
        
        // Process each agent from the table
        foreach ($agentsTable as $agent) {
            $userId = $agent['agent_id'];
            
            // Skip the first present agent that was used for the main record
            if ($userId == $record->user_id && 
                ($agent['status'] === 'present' || $agent['status'] === 'retard') &&
                $agent['heure_debut'] == $record->heure_debut &&
                $agent['heure_fin'] == $record->heure_fin) {
                
                // Attach this user to the main pointage record
                $record->users()->attach($userId);
                continue;
            }
            
            // Create a new pointage record for this agent with their specific data
            $newPointage = new Pointage([
                'project_id' => $record->project_id,
                'date' => $record->date,
                'is_jour_ferie' => $record->is_jour_ferie,
                'status' => $agent['status'],
                'heure_debut' => in_array($agent['status'], ['present', 'retard']) ? $agent['heure_debut'] : null,
                'heure_fin' => in_array($agent['status'], ['present', 'retard']) ? $agent['heure_fin'] : null,
                'commentaire' => $agent['commentaire'] ?? $record->commentaire,
                'user_id' => $userId, // For backward compatibility
                'heures_supplementaires_approuvees' => $record->heures_supplementaires_approuvees,
            ]);
            
            $newPointage->save();
            
            // Calculate hours worked, overtime, etc.
            $newPointage->calculateHoursWorked();
            $newPointage->save();
            
            // Attach this user to the new pointage
            $newPointage->users()->attach($userId);
        }
        
        // Calculate hours worked, overtime, etc. for the main record
        $record->calculateHoursWorked();
        $record->save();
    }
}
