<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaElectronica extends Model
{
  protected $table = 'factura_electronica';

  use HasFactory;

  public $fillable = [
    'id_rango_numeracion',
    'documento',
    'codigo_referencia',
    'observacion',
    'forma_pago',
    'fecha_vencimiento_factura',
    'metodo_pago',
    'codigo_metodo_pago',
    'tipo_operacion',
    'numero_factura',
    'id_cliente',
    'id_venta',
    'id_usuario'
  ];

  public function rangoNumeracion()
  {
    return $this->belongsTo(RangosNumeracion::class, 'id_rango_numeracion');
  }

  public function cliente()
  {
    return $this->belongsTo(Cliente::class, 'id_cliente');
  }

  public function venta()
  {
    return $this->belongsTo(Venta::class, 'id_venta');
  }

  public function users()
  {
    return $this->belongsTo(User::class, 'id_usuario');
  }
}
