<?php

namespace App\Filament\Resources\InventarioResource\Widgets;

use App\Models\Inventario;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductosPorAgotarse extends BaseWidget
{

  protected static ?string $heading = 'Productos Por Agotarse';

  protected int|string|array $columnSpan = 1;

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Inventario::query()->whereBetween('cantidad_disponible', [1, 5])
      )
      ->columns([
        TextColumn::make('producto.nombre_producto')
          ->label('Producto'),

        TextColumn::make('producto.marca')
          ->label('Marca'),

        TextColumn::make('cantidad_disponible')
          ->label('Cantidad Disponible')
          ->badge(),
      ]);
  }
}
