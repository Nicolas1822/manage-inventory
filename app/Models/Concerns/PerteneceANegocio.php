<?php

namespace App\Models\Concerns;

use App\Exceptions\SinNegocioException;
use App\Models\Scopes\NegocioScope;
use App\Models\Sesion;
use App\Services\NegocioActual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo cuyos registros pertenecen a un negocio (columna id_sesion).
 */
trait PerteneceANegocio
{
    public static function bootPerteneceANegocio(): void
    {
        static::addGlobalScope(new NegocioScope);

        // Con usuario autenticado, el registro siempre va a su negocio activo,
        // aunque llegue otro id_sesion. Sin usuario (seeders, consola) se respeta
        // el valor explícito y la BD rechaza el registro si falta.
        static::creating(function (Model $model): void {
            $negocio = app(NegocioActual::class);

            if (! $negocio->hayUsuario()) {
                return;
            }

            if (! $negocio->tieneNegocio()) {
                throw new SinNegocioException;
            }

            $model->setAttribute('id_sesion', $negocio->id());
        });
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Sesion::class, 'id_sesion');
    }
}
