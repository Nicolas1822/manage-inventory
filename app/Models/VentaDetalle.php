<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
  use HasFactory;

  protected $table = 'venta_detalle';

  protected $fillable = [
    'id_venta',
    'id_producto',
    'id_usuario',
    'cantidad_vendida_producto'
  ];

  public function users() {
    return $this->belongsTo(User::class, 'id_usuario');
  }

  public function venta() {
    return $this->belongsTo(Venta::class, 'id_venta');
  }

  public function producto() {
    return $this->belongsTo(Producto::class, 'id_producto');
}
}
