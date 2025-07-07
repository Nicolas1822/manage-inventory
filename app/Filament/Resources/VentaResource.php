<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Models\Venta;
use App\Models\Producto;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;

class VentaResource extends Resource
{
  protected static ?string $model = Venta::class;

  protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

  protected static ?int $navigationSort = 3;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Grid::make(3)
          ->schema([
            Card::make()
              ->columnSpan(1)
              ->schema([
                Hidden::make('fecha_venta')
                  ->dehydrated()
                  ->default(now()),
                Section::make('Agregar Productos')
                  ->schema([
                    Select::make('producto_id')
                      ->label('Nombre del Producto')
                      ->placeholder('Seleccione el producto')
                      ->options(function () {
                        return Producto::query()
                          ->join('inventario', 'producto.id', '=', 'inventario.id_producto')
                          ->where('inventario.id_usuario', auth()->id())
                          ->select('producto.id', 'producto.nombre_producto', 'producto.precio_unidad')
                          ->distinct()
                          ->orderBy('producto.nombre_producto')
                          ->get()
                          ->mapWithKeys(fn($producto) => [
                            $producto->id => $producto->nombre_producto . ' - $' . number_format($producto->precio_unidad, 0, ',', '.')
                          ]);
                      })
                      ->searchable()
                      ->live()
                      ->afterStateUpdated(function ($state, Set $set) {
                        if ($precioProducto = Producto::find($state)) {
                          $set('precio_unidad', number_format($precioProducto->precio_unidad, 0, '', ','));
                        }
                      }),

                    TextInput::make('precio_unidad')
                      ->label('Precio')
                      ->prefix('$')
                      ->disabled(),

                    Actions::make([
                      Action::make('agregar_producto')
                        ->label('Agregar a la lista')
                        ->button()
                        ->action(function (Set $set, Get $get) {
                          $productoId = $get('producto_id');
                          $cantidad = 1;

                          if (!$productoId)
                            return;

                          $obtenerProducto = Producto::find($productoId);
                          $productosAgregados = $get('productos') ?: [];

                          // Agregar nuevo producto
                          $productosAgregados[] = [
                            'id_producto' => $obtenerProducto->id,
                            'nombre' => $obtenerProducto->nombre_producto,
                            'precio' => number_format($obtenerProducto->precio_unidad, 0, '', ','),
                            'cantidad' => $cantidad,
                          ];

                          $set('productos', $productosAgregados);

                          self::modificarTotal($get, $set);
                          // Resetear campos
                          $set('producto_id', null);
                          $set('precio_unidad', null);
                        })
                    ])
                      ->alignCenter(),
                  ]),
              ]),

            Card::make()
              ->columnSpan(2)
              ->schema([
                Section::make('Productos Agregados')

                  ->schema([
                    Repeater::make('productos')
                      ->label(false)
                      ->defaultItems(0)
                      ->schema([
                        TextInput::make('nombre')
                          ->label('Producto')
                          ->disabled(),

                        TextInput::make('precio')
                          ->label('Precio')
                          ->disabled(),

                        TextInput::make('cantidad')
                          ->label('Cantidad')
                          ->numeric()
                          ->required(true)
                          ->minValue(1)
                          ->default(1)
                          ->live()
                          ->afterStateUpdated(function (Get $get, Set $set) {
                            self::modificarTotal($get, $set);

                          }),
                      ])
                      ->columns(3)
                      ->rule(['array', 'min:1'])
                      ->validationMessages([
                        'min' => 'Agrega al menos un producto para realizar la venta',
                      ])
                      ->hint(function (Get $get): ?string {
                        if (count((array) $get('productos')) === 0) {
                          return 'Debes agregar al menos un producto para realizar la venta';
                        }
                        return null;
                      })
                      ->hintColor('danger')
                      ->columnSpanFull()
                      ->reorderable(false)
                      ->addable(false)
                      ->live()
                      ->afterStateUpdated(function (Get $get, Set $set) {
                        self::modificarTotal($get, $set);
                      })

                      ->deleteAction(
                        fn(Action $action) => $action->after(fn(Get $get, Set $set) => self::modificarTotal($get, $set))
                      ),

                    Placeholder::make('total_label')
                      ->label('Total:')
                      ->content(function (Get $get) {
                        $totalProductosAgregados = $get('productos') ?: [];
                        $total = collect($totalProductosAgregados)
                          ->reduce(function ($carry, $item) {
                            $precio = str_replace(',', '', $item['precio']);
                            return $carry + ((float) $precio * (int) ($item['cantidad'] ?? 1));
                          }, 0);

                        return '$' . number_format($total, 0, ',', '.');
                      })
                      ->extraAttributes(['class' => 'text-lg font-bold']),

                  ]),

              ]),
            Hidden::make('total_venta')
              ->default(0)
          ]),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('fecha_venta')
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_venta')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
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

  public static function modificarTotal(Get $get, Set $set): void
  {
    $totalProductosAgregados = $get('productos') ?: [];
    $total = collect($totalProductosAgregados)
      ->reduce(function ($carry, $item) {
        $precio = str_replace(',', '', $item['precio']);
        return $carry + ((float) $precio * (int) ($item['cantidad'] ?? 1));
      }, 0);

    $set('total_venta', $total);
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
      'index' => Pages\ListVentas::route('/'),
      'create' => Pages\CreateVenta::route('/create'),
      'edit' => Pages\EditVenta::route('/{record}/edit'),
    ];
  }
}
