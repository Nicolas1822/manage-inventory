<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use Filament\Resources\Pages\EditRecord;

class EditVenta extends EditRecord
{
  protected static string $resource = VentaResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }

  protected function getRedirectUrl(): string
  {
    return static::getResource()::getUrl('index');
  }
}
