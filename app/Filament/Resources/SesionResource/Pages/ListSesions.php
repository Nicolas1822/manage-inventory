<?php

namespace App\Filament\Resources\SesionResource\Pages;

use App\Filament\Resources\SesionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSesions extends ListRecords
{
    protected static string $resource = SesionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
