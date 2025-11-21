<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tributo extends Model
{
  protected $table = 'tributos';

  use HasFactory;

  protected $fillable = ['id', 'code', 'name', 'description'];

  public function producto()
  {
    return $this->hasMany(Producto::class, 'id_tributo');
  }
}
