<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
  protected $table = 'municipios';

  use HasFactory;

  protected $fillable = ['id', 'code', 'name', 'department'];
}
