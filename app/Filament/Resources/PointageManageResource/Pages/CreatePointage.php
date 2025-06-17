<?php

namespace App\Filament\Resources\PointageManageResource\Pages;

use App\Filament\Resources\PointageManageResource;
use App\Models\Pointage;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreatePointage extends CreateRecord
{
    protected static string $resource = PointageManageResource::class;

    protected ?array $agentsTableData = null;

    /**
     * Recursively flattens and filters the agents_table data.
     *
     * @param array $items
     * @return array
     */
    private function deepExtractAgents(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                if (isset($item['agent_id']) && is_numeric($item['agent_id'])) {
                    $result[] = $item;
                } else {
                    // Recursively process nested arrays
                    $result = array_merge($result, $this->deepExtractAgents($item));
                }
            }
        }
        return $result;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawAgentsTable = $data['agents_table'] ?? [];
        if (empty($rawAgentsTable)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'agents_table' => 'Le tableau des agents ne peut pas être vide.'
            ]);
        }

        // Flatten the deeply nested array and filter for valid agent data
        $this->agentsTableData = $this->deepExtractAgents($rawAgentsTable);

        if (empty($this->agentsTableData)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'agents_table' => 'Au moins un agent valide doit être sélectionné pour le pointage.'
            ]);
        }

        // The first agent's data will be used for the main record
        $firstAgent = $this->agentsTableData[0];

        $data['user_id'] = $firstAgent['agent_id'];
        $data['status'] = $firstAgent['status'];
        $data['heure_debut'] = in_array($firstAgent['status'], ['present', 'retard']) ? $firstAgent['heure_debut'] : null;
        $data['heure_fin'] = in_array($firstAgent['status'], ['present', 'retard']) ? $firstAgent['heure_fin'] : null;
        $data['heures_travaillees'] = in_array($firstAgent['status'], ['present', 'retard']) ? ($firstAgent['total_hours'] ?? 0) : 0;
        $data['commentaire'] = $firstAgent['commentaire'] ?? null;

        // Unset agents_table from the main data array to prevent it from being saved on the Pointage model directly
        unset($data['agents_table']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // The main record has already been created for the first agent.
        // Now, create pointage records for the rest of the agents.
        if (count($this->agentsTableData) > 1) {
            $otherAgents = array_slice($this->agentsTableData, 1);

            foreach ($otherAgents as $agentData) {
                try {
                    Pointage::create([
                        'project_id' => $this->record->project_id,
                        'date' => $this->record->date,
                        'is_jour_ferie' => $this->record->is_jour_ferie,
                        'heures_supplementaires_approuvees' => $this->record->heures_supplementaires_approuvees,
                        'user_id' => $agentData['agent_id'],
                        'status' => $agentData['status'],
                        'heure_debut' => in_array($agentData['status'], ['present', 'retard']) ? $agentData['heure_debut'] : null,
                        'heure_fin' => in_array($agentData['status'], ['present', 'retard']) ? $agentData['heure_fin'] : null,
                        'heures_travaillees' => in_array($agentData['status'], ['present', 'retard']) ? ($agentData['total_hours'] ?? 0) : 0,
                        'commentaire' => $agentData['commentaire'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create additional pointage record: ' . $e->getMessage(), [
                        'agent_data' => $agentData,
                        'main_record' => $this->record->toArray(),
                    ]);
                    // Optionally, you can notify the user about the failure.
                }
            }
        }
    }
}