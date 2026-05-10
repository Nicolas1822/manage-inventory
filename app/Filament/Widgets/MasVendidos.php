<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class MasVendidos extends BaseWidget
{
    protected static ?string $heading = 'Productos Más Vendidos';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Producto::query()
                    ->select('producto.*')
                    ->join('venta_detalle', 'producto.id', '=', 'venta_detalle.id_producto')
                    ->selectRaw('SUM(venta_detalle.cantidad_vendida_producto) as total_vendido')
                    ->selectRaw('SUM(venta_detalle.cantidad_vendida_producto * producto.precio_unidad) as monto_total')
                    ->groupBy('producto.id')
                    ->orderByDesc('total_vendido')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre_producto')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_vendido')
                    ->label('Cant. Vendida')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('monto_total')
                    ->label('Total Vendido ($)')
                    ->money('cop')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('mes')
                    ->options([
                        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
                        '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
                        '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
                        '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
                    ])
                    ->label('Filtrar por Mes')
                    ->default(date('m'))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereMonth('venta_detalle.created_at', $data['value']);
                        }
                    }),

                SelectFilter::make('anio')
                    ->options(function () {
                        $años = range(date('Y'), date('Y') - 5);
                        return array_combine($años, $años);
                    })
                    ->label('Filtrar por Año')
                    ->default(date('Y'))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereYear('venta_detalle.created_at', $data['value']);
                        }
                    }),
            ])
            ->filtersFormColumns(2);
    }
}
