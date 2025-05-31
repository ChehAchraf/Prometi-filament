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
        // Extract default agents and exceptions data
        $defaultAgents = $data['default_agents'] ?? [];
        $exceptions = $data['exceptions'] ?? [];
        
        // Create a map of exception agents by ID for easy lookup
        $exceptionAgentIds = [];
        foreach ($exceptions as $exception) {
            $exceptionAgentIds[] = $exception['agent_id'];
        }
        
        // For backward compatibility, set user_id to the first default agent if available
        if (!empty($defaultAgents)) {
            $data['user_id'] = $defaultAgents[0];
        } else {
            $data['user_id'] = 1; // Default user ID
        }
        
        // Set status from default_status
        if (isset($data['default_status'])) {
            $data['status'] = $data['default_status'];
        }
        
        // Set time fields from default values
        if (isset($data['default_heure_debut'])) {
            $data['heure_debut'] = $data['default_heure_debut'];
        }
        
        if (isset($data['default_heure_fin'])) {
            $data['heure_fin'] = $data['default_heure_fin'];
        }
        
        // Make sure heures_supplementaires_approuvees is handled
        if (!isset($data['heures_supplementaires_approuvees'])) {
            $data['heures_supplementaires_approuvees'] = false;
        }
        
        // Remove fields that aren't in the Pointage model
        unset($data['default_agents']);
        unset($data['default_status']);
        unset($data['default_heure_debut']);
        unset($data['default_heure_fin']);
        unset($data['exceptions']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Get the created record
        $record = $this->record;
        
        // Get the default agents and exceptions data from the form
        $defaultAgents = $this->data['default_agents'] ?? [];
        $exceptions = $this->data['exceptions'] ?? [];
        
        // Map exception agents by ID for easy lookup
        $exceptionAgentIds = [];
        foreach ($exceptions as $exception) {
            $exceptionAgentIds[] = $exception['agent_id'];
        }
        
        // Get the default agents that don't have exceptions
        $defaultAgentsWithoutExceptions = array_diff($defaultAgents, $exceptionAgentIds);
        
        // Sync the default agents (without exceptions) with the main pointage record
        $record->users()->sync($defaultAgentsWithoutExceptions);
        
        // Process exceptions - create separate pointage records for each
        foreach ($exceptions as $exception) {
            $userId = $exception['agent_id'];
            
            // Create a new pointage record for this agent with their specific data
            $newPointage = new Pointage([
                'project_id' => $record->project_id,
                'date' => $record->date,
                'is_jour_ferie' => $record->is_jour_ferie,
                'status' => $exception['status'],
                'heure_debut' => in_array($exception['status'], ['present', 'retard']) ? $exception['heure_debut'] : null,
                'heure_fin' => in_array($exception['status'], ['present', 'retard']) ? $exception['heure_fin'] : null,
                'commentaire' => $exception['commentaire'] ?? $record->commentaire,
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
