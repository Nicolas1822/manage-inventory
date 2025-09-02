<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventario extends EditRecord
{
  protected static string $resource = InventarioResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }
}
