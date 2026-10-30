<?php

namespace App\Filament\Concerns;

use App\Services\NegocioActual;
use Closure;
use Illuminate\Validation\Rules\Exists;

/**
 * Impide crear registros a quien no tiene un negocio activo.
 */
trait RequiereNegocio
{
    public static function canCreate(): bool
    {
        return app(NegocioActual::class)->tieneNegocio();
    }

    /**
     * Para ->exists(): el id elegido debe pertenecer al negocio activo.
     * Las reglas de validación consultan la BD sin el global scope.
     */
    protected static function delNegocioActivo(): Closure
    {
        return fn (Exists $rule): Exists => $rule->where('id_sesion', app(NegocioActual::class)->id());
    }
}
