<?php

namespace App\Filament\Resources\SesionResource\Pages;

use App\Filament\Resources\SesionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSesion extends CreateRecord
{
    protected static string $resource = SesionResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
