<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointages extends ListRecords
{
    protected static string $resource = PointageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
