<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
  protected $table = 'clientes';

  protected $fillable = [
    'id_documento_identificacion',
    'identificacion',
    'dv',
    'empresa',
    'nombre_comercial',
    'nombres',
    'direccion',
    'email',
    'n_celular',
    'tipo_organizacion',
    'id_tributo',
    'id_municipio',
    'id_usuario',
  ];

  use HasFactory;

  public function users()
  {
    return $this->belongsTo(User::class, 'id_usuario');
  }

  public function facturaElectronica() {
    return $this->hasMany(FacturaElectronica::class, 'id_cliente');
  }
}
