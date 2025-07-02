<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;

class Inventario extends Model
{
  protected $table = 'inventario';

  use HasFactory;

  protected $fillable = [
    'cantidad_disponible',
    'id_producto',
    'id_usuario',
  ];

  public function producto()
  {
    return $this->belongsTo(Producto::class, 'id_producto');
  }

  public function users() {
    return $this->belongsTo(User::class, 'id_usuario');
  }
}
