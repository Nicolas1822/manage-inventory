<?php

namespace App\Observers;

use App\Models\Producto;
use App\Models\Inventario;

class ProductoObserver
{
  /**
   * Handle the Producto "created" event.
   */
  public function created(Producto $producto): void
  {
    Inventario::create([
        'id_producto' => $producto->id,
        'id_usuario' => auth()->id(),
        'id_factura' => $producto->id_factura,
        'cantidad_disponible' => $producto->cantidad_total_inicial,
        'cantidad_total_inicial' => $producto->cantidad_total_inicial,
        'lote_producto' => $producto->lote_producto,
        'nombre_producto' => $producto->nombre_producto,
        'precio_unidad' => $producto->precio_unidad,
        'marca' => $producto->marca,
    ]);
  }

  /**
   * Handle the Producto "updated" event.
   */
  public function updated(Producto $producto): void
  {
    //
  }

  /**
   * Handle the Producto "deleted" event.
   */
  public function deleted(Producto $producto): void
  {
    // Elimina el inventario asociado
    $producto->inventario()->delete();
  }

  /**
   * Handle the Producto "restored" event.
   */
  public function restored(Producto $producto): void
  {
    //
  }

  /**
   * Handle the Producto "force deleted" event.
   */
  public function forceDeleted(Producto $producto): void
  {
    //
  }
}
