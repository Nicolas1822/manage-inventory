<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadDeMedida extends Model
{
  protected $table = 'unidades_de_medida';

  use HasFactory;

  protected $fillable = ['id', 'code', 'name'];

  public function producto()
  {
    return $this->hasMany(Producto::class, 'id_unidad_medida');
  }

}
