<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Producto;
use App\Models\VentaDetalle;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
