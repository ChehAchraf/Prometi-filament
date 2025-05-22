<x-filament-panels::page>
    <div>
        <h2 class="text-xl font-bold mb-4">Rapports de pointage</h2>
        <p class="mb-6">Utilisez les filtres ci-dessous pour générer des rapports détaillés sur le pointage des collaborateurs.</p>
    </div>
    
    <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        {{ $this->form }}
        
        <x-filament::button
            wire:click="generateReport"
            class="mt-4"
        >
            Générer le rapport
        </x-filament::button>
    </div>
    
    <div class="mt-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Résultats du rapport</h3>
        </div>
        
        @livewire('pointage-report-table', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'projectId' => $this->projectId,
            'reportType' => $this->reportType,
        ])
    </div>
</x-filament-panels::page>
