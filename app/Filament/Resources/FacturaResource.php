<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacturaResource\Pages;
use App\Filament\Resources\FacturaResource\RelationManagers;
use App\Models\Factura;
use Filament\Tables\Filters\Filter;
;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\RawJs;

class FacturaResource extends Resource
{
  protected static ?string $model = Factura::class;

  protected static ?string $navigationIcon = 'heroicon-o-newspaper';

protected static ?int $navigationSort = 2;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('codigo_factura')
          ->required()
          ->numeric(),
        Forms\Components\DatePicker::make('fecha_emision')
          ->required(),
        Forms\Components\TextInput::make('total_factura')
          ->required()
          ->prefix('$')
          ->mask(RawJs::make('$money($input)'))
          ->stripCharacters(characters: ',')
          ->numeric(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('codigo_factura')
          ->numeric()
          ->searchable('codigo_factura')
          ->formatStateUsing(fn($state): string => str_replace('.', '', $state))
          ->sortable(),
        Tables\Columns\TextColumn::make('fecha_emision')
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_factura')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->modifyQueryUsing(function (Builder $query) {
        return $query
          ->join('producto', 'factura.id', '=', 'producto.id_factura')
          ->join('inventario', 'producto.id', '=', 'inventario.id_producto')
          ->where('inventario.id_usuario', auth()->id())
          ->select(
            'factura.id',
            'factura.codigo_factura',
            'factura.fecha_emision',
            'factura.total_factura')
          ->distinct();
      })
      ->filters([
        Filter::make('fecha_emision')
          ->form([
            DatePicker::make('fecha_emision_desde')->maxDate(now()),
            DatePicker::make('fecha_emision_hasta')->maxDate(now())
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                $data['fecha_emision_desde'],
                fn(Builder $query, $date): Builder => $query->whereDate('fecha_emision', '>=', $date),
              )
              ->when(
                $data['fecha_emision_hasta'],
                fn(Builder $query, $date): Builder => $query->whereDate('fecha_emision', '<=', $date),
              );
          })
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
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
      'index' => Pages\ListFacturas::route('/'),
      'create' => Pages\CreateFactura::route('/create'),
      'edit' => Pages\EditFactura::route('/{record}/edit'),
    ];
  }
}
