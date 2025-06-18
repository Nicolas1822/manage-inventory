<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFactura extends CreateRecord
{
  protected static string $resource = FacturaResource::class;

  protected function getRedirectUrl(): string
  {
    return static::getResource()::getUrl('index');
  }
}
