<?php

namespace App\Filament\Resources\FacturaElectronicaResource\Pages;

use App\Filament\Resources\FacturaElectronicaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacturaElectronica extends EditRecord
{
  protected static string $resource = FacturaElectronicaResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make(),
    ];
  }
}
