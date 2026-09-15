<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateProducto extends CreateRecord
{
    protected static string $resource = ProductoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->action('create')
            ->keyBindings(['mod+s']);
    }
}
