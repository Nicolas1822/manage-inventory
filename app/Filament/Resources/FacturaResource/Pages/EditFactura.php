<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFactura extends EditRecord
{
  protected static string $resource = FacturaResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }
  protected function getRedirectUrl(): string
  {
    return static::getResource()::getUrl('index');
  }
}
