<?php
namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Inventario;

class CreateVenta extends CreateRecord
{
  protected static string $resource = VentaResource::class;

  protected function getRedirectUrl(): string
  {
    return static::getResource()::getUrl('index');
  }

  protected function handleRecordCreation(array $data): Venta
  {
    foreach ($data['productos'] as $producto) {
      $inventario = Inventario::query()
        ->join('producto', 'inventario.id_producto', '=', 'producto.id')
        ->select('producto.nombre_producto', 'inventario.cantidad_disponible')
        ->where('inventario.id_producto', $producto['id_producto'])
        ->where('inventario.id_usuario', auth()->id())
        ->first();

      if (!$inventario || $inventario->cantidad_disponible < $producto['cantidad_vendida_producto']) {

        Notification::make()
          ->title('Inventario Insuficiente')
          ->body("No hay suficiente stock para el producto {$inventario->nombre_producto}. Disponibles: " . ($inventario?->cantidad_disponible ?? 0))
          ->danger()
          ->send();

        throw new Halt();
      }
    }

    $venta = $this->crearVenta($data);

    return $venta;
  }

  protected function crearVenta(array $data)
  {
    $venta = Venta::create([
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
      $obtenerPoducto = Producto::find($producto['id_producto']);
      if ($obtenerPoducto) {
        $obtenerPoducto->cantidad_vendida += $producto['cantidad_vendida_producto'];
        $obtenerPoducto->save();
      }
    }

    foreach ($data['productos'] as $producto) {
      $inventario = Inventario::where('id_producto', $producto['id_producto'])
        ->where('id_usuario', auth()->id())
        ->first();

      if ($inventario) {
        $inventario->cantidad_disponible -= $producto['cantidad_vendida_producto'];
        $inventario->save();
      }
    }
  }
}
