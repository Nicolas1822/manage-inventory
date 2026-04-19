<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\Cliente;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use GuzzleHttp\Client;

class CreateCliente extends CreateRecord
{
  protected static string $resource = ClienteResource::class;

  protected function getRedirectUrl(): string
  {
    return static::getResource()::getUrl('index');
  }

  protected function handleRecordCreation(array $data): Cliente
  {
    $data['id_usuario'] = auth()->id();
    $crearCliente = Cliente::create($data, );
    return $crearCliente;
  }
}
