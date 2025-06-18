<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
  protected $table = 'factura';

  use HasFactory;

  protected $fillable = ['codigo_factura', 'fecha_emision', 'total_factura', 'id_usuario'];

  public function producto()
  {
    return $this->belongsTo(Inventario::class, 'id_factura');
  }

  public function users()  {
    return $this->hasMany(User::class, 'id_usuario');
  }
}
