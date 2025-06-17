<?php

namespace App\Filament\Resources\PointageManageResource\Pages;

use App\Filament\Resources\PointageManageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointages extends ListRecords
{
    protected static string $resource = PointageManageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 