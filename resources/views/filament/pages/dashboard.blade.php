<x-filament-panels::page>
    <div>
        <h2 class="text-xl font-bold mb-4">Suivi du pointage des collaborateurs sur chantier</h2>
        <p class="mb-6">Bienvenue sur le tableau de bord de gestion du pointage. Utilisez les widgets ci-dessous pour visualiser les données de pointage.</p>
    </div>
    
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-6">
        @foreach ($this->getWidgets() as $widget)
            @if ($widget === \App\Filament\Widgets\PointageStatsWidget::class)
                @livewire($widget)
            @endif
        @endforeach
    </div>
    
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-4">Évolution des heures travaillées</h3>
    </div>
    
    <div class="grid grid-cols-1 gap-4 mb-6">
        @foreach ($this->getWidgets() as $widget)
            @if ($widget === \App\Filament\Widgets\PointageChartWidget::class)
                @livewire($widget)
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
