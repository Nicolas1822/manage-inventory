<?php

namespace App\Filament\Resources\InventarioResource\Widgets;

use App\Models\Inventario;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class ProductosAgotados extends BaseWidget
{
  protected static ?string $heading = 'Productos Agotados';

  protected int|string|array $columnSpan = 1;

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Inventario::query()->where('cantidad_disponible', 0)
      )
      ->columns([
        TextColumn::make('producto.nombre_producto')
          ->label('Producto'),

        TextColumn::make('producto.marca')
          ->label('Marca'),

        TextColumn::make('cantidad_disponible')
          ->label('Cantidad Disponible')
          ->badge()
          ->color('danger'),
      ]);
  }
}
