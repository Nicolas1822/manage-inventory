<?php

namespace App\Observers;

use App\Models\Factura;

class FacturaObserver
{
  public function creating(Factura $factura): void
  {
    if (auth()->check()) {
      $factura->id_usuario = auth()->id();
    }
  }
}
