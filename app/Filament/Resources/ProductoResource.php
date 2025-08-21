<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Inventario;
use App\Models\Producto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\RawJs;
use Filament\Tables\Filters\SelectFilter;

class ProductoResource extends Resource
{
  protected static ?string $model = Producto::class;

  protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

protected static ?int $navigationSort = 1;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('lote_producto')
          ->required()
          ->numeric(),
        Forms\Components\TextInput::make('nombre_producto')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('precio_unidad')
          ->required()
          ->prefix('$')
          ->mask(RawJs::make('$money($input)'))
          ->stripCharacters(characters: ',')
          ->numeric(),
        Forms\Components\TextInput::make('marca')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('cantidad_total_inicial')
          ->label('Unidades')
          ->numeric()
          ->required(),
        Forms\Components\Select::make('id_factura')
          ->relationship(
            name: 'factura',
            titleAttribute: 'codigo_factura',
            modifyQueryUsing: fn(Builder $query) => $query->where('id_usuario', auth()->id())
          )
          ->searchable()
          ->preload()
          ->createOptionForm([
            Forms\Components\TextInput::make('codigo_factura')
              ->numeric()
              ->required(),
            Forms\Components\DatePicker::make('fecha_emision')
              ->maxDate(now())
              ->required(),
            Forms\Components\TextInput::make('total_factura')
              ->required()
              ->prefix('$')
              ->mask(RawJs::make('$money($input)'))
              ->stripCharacters(',')
              ->numeric(),
            Forms\Components\Hidden::make('id_usuario')
              ->default(auth()->id())
              ->required(),
          ])
          ->label('Código factura')
          ->required()
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('lote_producto')
          ->label('Lote')
          ->numeric()
          ->formatStateUsing(fn($state): string => str_replace('.', '', $state))
          ->sortable(),
        Tables\Columns\TextColumn::make('nombre_producto')
          ->searchable(),
        Tables\Columns\TextColumn::make('marca')
          ->searchable(),
        Tables\Columns\TextColumn::make('precio_unidad')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('cantidad_total_inicial')
          ->label('Unidades')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('codigo_factura')
          ->numeric()
          ->label('Codigo factura')
          ->formatStateUsing(fn($state): string => str_replace('.', '', $state))
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->modifyQueryUsing(function (Builder $query) {
        return $query
          ->join('inventario', 'producto.id', '=', 'inventario.id_producto')
          ->join('factura', 'producto.id_factura', '=', 'factura.id')
          ->where('inventario.id_usuario', auth()->id())
          ->select(
            'producto.id',
            'producto.lote_producto',
            'producto.nombre_producto',
            'producto.precio_unidad',
            'producto.marca',
            'producto.cantidad_total_inicial',
            'producto.created_at',
            'factura.codigo_factura'
          );
      })
      ->filters([
        SelectFilter::make('lote_producto')
          ->label('Filtrar por Lote')
          ->options(function () {
            return Producto::query()
              ->join('inventario', 'producto.id', '=', 'inventario.id_producto')
              ->where('inventario.id_usuario', auth()->id())
              ->select('producto.lote_producto')
              ->distinct()
              ->orderBy('producto.lote_producto')
              ->pluck('producto.lote_producto', 'producto.lote_producto')
              ->toArray();
          })
          ->searchable()
          ->preload(),
        SelectFilter::make('id_factura')
          ->label('Filtrar por código factura')
          ->options(function () {
            return Inventario::query()
              ->join('producto', 'inventario.id_producto', 'producto.id')
              ->join('factura', 'producto.id_factura', '=', 'factura.id')
              ->select('factura.id', 'factura.codigo_factura')
              ->where('inventario.id_usuario', auth()->id())
              ->distinct()
              ->pluck('codigo_factura', 'id')
              ->toArray();
          })
          ->searchable()
          ->preload()
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
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
      'index' => Pages\ListProductos::route('/'),
      'create' => Pages\CreateProducto::route('/create'),
      'edit' => Pages\EditProducto::route('/{record}/edit'),
    ];
  }
}
