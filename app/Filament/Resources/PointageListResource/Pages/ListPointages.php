<?php

namespace App\Filament\Resources\PointageListResource\Pages;

use App\Filament\Resources\PointageListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointages extends ListRecords
{
    protected static string $resource = PointageListResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
} 