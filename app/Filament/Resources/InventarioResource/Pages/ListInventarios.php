<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Widgets\ProductosAgotados;
use App\Filament\Resources\InventarioResource\Widgets\ProductosPorAgotarse;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
  protected static string $resource = InventarioResource::class;

  protected function getHeaderActions(): array
  {
    return [];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      ProductosAgotados::class,
      ProductosPorAgotarse::class
    ];
  }
}
