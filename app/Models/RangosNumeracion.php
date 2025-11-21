<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RangosNumeracion extends Model
{
  protected $table = 'rangos_numeracion';

  use HasFactory;

  protected $fillable = [
    'id',
    'document',
    'prefix',
    'from',
    'to',
    'current',
    'resolution_number',
    'start_date',
    'end_date',
    'technical_key',
    'is_expired',
    'is_active'
  ];
}
