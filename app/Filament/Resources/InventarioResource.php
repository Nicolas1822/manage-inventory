<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventarioResource extends Resource
{
  protected static ?string $model = Inventario::class;

  protected static ?string $navigationIcon = 'heroicon-o-archive-box';

  protected static ?int $navigationSort = 4;


  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('cantidad_disponible')
          ->required()
          ->numeric(),
        Forms\Components\TextInput::make('id_producto')
          ->required()
          ->numeric(),
        Forms\Components\TextInput::make('id_usuario')
          ->required()
          ->numeric(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('producto.nombre_producto')
          ->searchable()
          ->numeric()
          ->label('Producto'),
        Tables\Columns\TextColumn::make('producto.marca')
          ->searchable()
          ->numeric()
          ->label('Marca'),
        Tables\Columns\TextColumn::make('producto.precio_unidad')
          ->numeric()
          ->sortable()
          ->label('Precio unidad'),
        Tables\Columns\TextColumn::make('cantidad_disponible')
          ->numeric()
          ->sortable()
          ->label('Stock disponible'),
        Tables\Columns\TextColumn::make('producto.cantidad_vendida')
          ->numeric()
          ->sortable()
          ->default(0)
          ->label('Cantidad vendida'),
        Tables\Columns\TextColumn::make('producto.cantidad_total_inicial')
          ->numeric()
          ->sortable()
          ->label('Cantidad inicial'),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->modifyQueryUsing(function () {
        return Inventario::query()
          ->where('id_usuario', auth()->id());
      })
      ->filters([
        //
      ])
      ->actions([
      ]);
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListInventarios::route('/'),
    ];
  }
}
