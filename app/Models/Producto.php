<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
  use HasFactory;

  protected $table = 'producto';

  protected $fillable = [
    'lote_producto',
    'nombre_producto',
    'precio_unidad',
    'marca',
    'cantidad_total_inicial',
    'cantidad_vendida',
    'id_factura'
  ];

  public function inventario()
  {
    return $this->hasOne(Inventario::class, 'id_producto');
  }

  public function factura() {
    return $this->belongsTo(Factura::class, 'id_factura');
  }

  public function ventaDetalle() {
    return $this->hasMany(VentaDetalle::class, 'id_producto');
  }
}
