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
    'porcentaje_descuento',
    'porcentaje_impuesto',
    'codigo_estandar_id',
    'excluido',
    'estado_disponibilidad',
    'id_tributo',
    'id_unidad_medida',
    'id_factura'
  ];

  protected function casts(): array
  {
    return [
      'excluido' => 'boolean',
    ];
  }

  public function inventario()
  {
    return $this->hasOne(Inventario::class, 'id_producto');
  }

  public function factura()
  {
    return $this->belongsTo(Factura::class, 'id_factura');
  }

  public function ventaDetalle()
  {
    return $this->hasMany(VentaDetalle::class, 'id_producto');
  }

  public function tributo()
  {
    return $this->belongsTo(Tributo::class, 'id_tributo');
  }

  public function unidadDeMedida()
  {
    return $this->belongsTo(UnidadDeMedida::class, 'id_unidad_medida');
  }
}
