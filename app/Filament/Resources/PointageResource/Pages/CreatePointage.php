<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use App\Models\Pointage;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreatePointage extends CreateRecord
{
    /**
     * Recursively extract all agent objects from a nested array
     */
    private function deepExtractAgents($array)
    {
        $agents = [];
        foreach ($array as $item) {
            if (is_array($item) && isset($item['agent_id']) && is_numeric($item['agent_id'])) {
                $agents[] = $item;
            } elseif (is_array($item)) {
                $agents = array_merge($agents, $this->deepExtractAgents($item));
            }
        }
        return $agents;
    }

    protected static string $resource = PointageResource::class;
    
    // Store agents_table data for use in afterCreate
    protected array $agentsTableData = [];
    
    // This method runs before the record is created
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If $data is an array of arrays (multi-section), extract the first element
        if (isset($data[0]) && is_array($data[0]) && isset($data[0]['agents_table'])) {
            $data = $data[0];
        }
        // Recursively flatten agents_table to extract all agent objects
        $rawAgentsTable = $data['agents_table'] ?? [];
        $agents = $this->deepExtractAgents($rawAgentsTable);
        $this->agentsTableData = $agents;
        // Set user_id to the first agent's agent_id if available and valid
        if (!empty($this->agentsTableData) && !empty($this->agentsTableData[0]['agent_id'])) {
            $data['user_id'] = $this->agentsTableData[0]['agent_id'];
        } else {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'agents_table' => 'Au moins un agent valide doit être sélectionné pour le pointage.'
            ]);
        }
        
        // Make sure heures_supplementaires_approuvees is handled
        if (!isset($data['heures_supplementaires_approuvees'])) {
            $data['heures_supplementaires_approuvees'] = false;
        }
        
        // Use the first agent's data for the main pointage record
        $firstAgent = $this->agentsTableData[0];
        $data['status'] = $firstAgent['status'];
        $data['heure_debut'] = in_array($firstAgent['status'], ['present', 'retard']) ? $firstAgent['heure_debut'] : null;
        $data['heure_fin'] = in_array($firstAgent['status'], ['present', 'retard']) ? $firstAgent['heure_fin'] : null;
        $data['heures_travaillees'] = in_array($firstAgent['status'], ['present', 'retard']) ? ($firstAgent['total_hours'] ?? 0) : 0;
        
        // Remove fields that aren't in the Pointage model
        unset($data['agents_table']);
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        Log::debug('Agents table data in afterCreate:', $this->agentsTableData);
        // Get the created record
        $record = $this->record;
        
        // Process each agent from the stored table data
        foreach ($this->agentsTableData as $agent) {
            // Skip if no agent_id is provided
            if (!isset($agent['agent_id']) || empty($agent['agent_id'])) {
                continue;
            }
            
            // Skip the first agent that was used for the main record
            if ($agent['agent_id'] == $record->user_id) {
                continue;
            }
            
            try {
                // Create a new pointage record for this agent
                $newPointage = new Pointage([
                    'user_id' => $agent['agent_id'],
                    'project_id' => $record->project_id,
                    'date' => $record->date,
                    'is_jour_ferie' => $record->is_jour_ferie,
                    'status' => $agent['status'],
                    'heure_debut' => in_array($agent['status'], ['present', 'retard']) ? $agent['heure_debut'] : null,
                    'heure_fin' => in_array($agent['status'], ['present', 'retard']) ? $agent['heure_fin'] : null,
                    'heures_travaillees' => in_array($agent['status'], ['present', 'retard']) ? ($agent['total_hours'] ?? 0) : 0,
                    'commentaire' => $agent['commentaire'] ?? null,
                    'heures_supplementaires_approuvees' => $record->heures_supplementaires_approuvees,
                ]);
                
                $newPointage->save();
                
                // Calculate hours worked, overtime, etc.
                $newPointage->calculateHoursWorked();
                $newPointage->save();
                
            } catch (\Exception $e) {
                Log::error('Error creating additional pointage: ' . $e->getMessage(), [
                    'agent_data' => $agent,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Calculate hours worked, overtime, etc. for the main record
        $record->calculateHoursWorked();
        $record->save();
    }
}
