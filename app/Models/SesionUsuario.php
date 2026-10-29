<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionUsuario extends Model
{
    use HasFactory;

    protected $table = 'sesion_usuario';

    protected $fillable = [
        'id_sesion',
        'id_user',
        // 'rol_en_sesion',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'id_sesion');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
