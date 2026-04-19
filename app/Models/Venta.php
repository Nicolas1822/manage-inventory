<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
  use HasFactory;

  protected $table = 'venta';

  protected $fillable = [
    'codigo_venta',
    'fecha_venta',
    'total_venta',
    'estado_factura_electroinca'
  ];

  public function ventaDetalle()
  {
    return $this->hasMany(VentaDetalle::class, 'id_venta');
  }

  public function facturaElectronica()
  {
    return $this->hasOne(FacturaElectronica::class, 'id_venta');
  }
}
