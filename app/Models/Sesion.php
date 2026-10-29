<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sesion extends Model
{
    use HasFactory;

    protected $table = 'sesion';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'sesion_usuario',
            'id_sesion',
            'id_user'
        )->withTimestamps();
    }

    public function sesionUsuarios(): HasMany
    {
        return $this->hasMany(SesionUsuario::class, 'id_sesion');
    }
}
