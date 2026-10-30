<?php

namespace App\Filament\Concerns;

use App\Services\NegocioActual;

/**
 * Impide crear registros a quien no tiene un negocio activo.
 */
trait RequiereNegocio
{
    public static function canCreate(): bool
    {
        return app(NegocioActual::class)->tieneNegocio();
    }
}
