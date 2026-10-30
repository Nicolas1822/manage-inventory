<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Venta
    {
        // Bloquea el inventario en orden de producto para que dos ventas simultáneas
        // no se esperen en cruz (deadlock); si aun así ocurre, se reintenta.
        $data['productos'] = collect($data['productos'])->sortBy('id_producto')->values()->all();

        return DB::transaction(function () use ($data) {
            foreach ($data['productos'] as $producto) {
                $inventario = Inventario::query()
                    ->join('producto', 'inventario.id_producto', '=', 'producto.id')
                    ->select('producto.nombre_producto', 'inventario.cantidad_disponible')
                    ->where('inventario.id_producto', $producto['id_producto'])
                    ->lockForUpdate()
                    ->first();

                if (! $inventario || $inventario->cantidad_disponible < $producto['cantidad_vendida_producto']) {
                    $nombre = $inventario?->nombre_producto
                      ?? Producto::find($producto['id_producto'])?->nombre_producto
                      ?? 'seleccionado';

                    Notification::make()
                        ->title('Inventario Insuficiente')
                        ->body("No hay suficiente stock para el producto {$nombre}. Disponibles: ".($inventario?->cantidad_disponible ?? 0))
                        ->danger()
                        ->send();

                    throw new Halt;
                }
            }

            return $this->crearVenta($data);
        }, 3);
    }

    protected function crearVenta(array $data)
    {
        $codigoVenta = rand(1000000, 2000000);
        $venta = Venta::create([
            'codigo_venta' => $codigoVenta,
            'fecha_venta' => $data['fecha_venta'],
            'total_venta' => $data['total_venta'],
        ]);

        $venta->ventaDetalle()->createMany(
            collect($data['productos'])->map(function ($producto) {
                return [
                    'id_producto' => $producto['id_producto'],
                    'id_usuario' => auth()->id(),
                    'cantidad_vendida_producto' => $producto['cantidad_vendida_producto'],
                ];
            })->toArray()
        );

        $this->modificarCantidadVendidaProducto($data);

        return $venta;
    }

    protected function modificarCantidadVendidaProducto(array $data): void
    {
        foreach ($data['productos'] as $producto) {
            $obtenerProducto = Producto::find($producto['id_producto']);
            if ($obtenerProducto) {
                $obtenerProducto->cantidad_vendida += $producto['cantidad_vendida_producto'];
                $obtenerProducto->save();
            }

            $inventario = Inventario::where('id_producto', $producto['id_producto'])
                ->first();

            if ($inventario) {
                $inventario->cantidad_disponible -= $producto['cantidad_vendida_producto'];
                $inventario->save();

                if ($inventario->cantidad_disponible <= 0 && $obtenerProducto) {
                    $obtenerProducto->update(['estado_disponibilidad' => 1]);
                }
            }
        }
    }
}
