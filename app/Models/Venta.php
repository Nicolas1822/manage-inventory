<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
  use HasFactory;

  protected $table = 'venta';

  protected $fillable = [
    'fecha_venta',
    'total_venta'
  ];

  public function ventaDetalle() {
    return $this->hasMany(VentaDetalle::class, 'id_venta');
  }
}
