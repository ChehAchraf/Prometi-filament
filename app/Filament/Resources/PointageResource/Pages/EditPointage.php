<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use App\Models\User;
use App\Models\Pointage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPointage extends EditRecord
{
    protected static string $resource = PointageResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the current record
        $record = $this->record;
        
        // Initialize agents_table with the current record's data
        $agentsTable = [];
        
        // Get all pointages for the same project and date
        $relatedPointages = Pointage::where('project_id', $record->project_id)
            ->where('date', $record->date)
            ->get();
            
        // Process each pointage record to get agent data
        foreach ($relatedPointages as $pointage) {
            $users = $pointage->users()->get();
            
            foreach ($users as $user) {
                $agentsTable[] = [
                    'agent_id' => $user->id,
                    'agent_name' => $user->name,
                    'status' => $pointage->status,
                    'heure_debut' => $pointage->heure_debut,
                    'heure_fin' => $pointage->heure_fin,
                    'commentaire' => $pointage->commentaire,
                ];
            }
        }
        
        $data['agents_table'] = $agentsTable;
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Get the record being edited
        $record = $this->record;
        
        // Get the agents table data from the form
        $agentsTable = $this->data['agents_table'] ?? [];
        
        // Delete all related pointages for this project and date except the current one
        Pointage::where('project_id', $record->project_id)
            ->where('date', $record->date)
            ->where('id', '!=', $record->id)
            ->delete();
            
        // Clear existing users from the main record
        $record->users()->detach();
        
        // Process each agent from the table
        foreach ($agentsTable as $agent) {
            $userId = $agent['agent_id'];
            
            // Skip the first present agent that was used for the main record
            if (($agent['status'] === 'present' || $agent['status'] === 'retard') &&
                $agent['heure_debut'] == $record->heure_debut &&
                $agent['heure_fin'] == $record->heure_fin) {
                
                // Use this as the main record
                $record->status = $agent['status'];
                $record->user_id = $userId; // For backward compatibility
                $record->commentaire = $agent['commentaire'] ?? $record->commentaire;
                $record->save();
                
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
