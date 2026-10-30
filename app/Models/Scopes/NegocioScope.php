<?php

namespace App\Models\Scopes;

use App\Services\NegocioActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limita las consultas al negocio activo del usuario autenticado.
 *
 * Sin usuario (API n8n, consola) no filtra. Con usuario sin negocio activo
 * devuelve un resultado vacío.
 */
class NegocioScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $negocio = app(NegocioActual::class);

        if (! $negocio->hayUsuario()) {
            return;
        }

        if ($negocio->tieneNegocio()) {
            $builder->where($model->qualifyColumn('id_sesion'), $negocio->id());

            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
