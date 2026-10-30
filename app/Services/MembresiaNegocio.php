<?php

namespace App\Services;

use App\Exceptions\UsuarioYaAsignadoException;
use App\Models\Sesion;
use App\Models\User;

/**
 * Altas y bajas de usuarios en un negocio. Un usuario solo puede estar en uno.
 */
class MembresiaNegocio
{
    /**
     * @throws UsuarioYaAsignadoException si el usuario ya está en otro negocio.
     */
    public function asignar(User $usuario, Sesion $negocio): void
    {
        $actual = $usuario->negocios()->first();

        if ($actual === null) {
            $usuario->negocios()->attach($negocio->id);

            return;
        }

        if ($actual->id !== $negocio->id) {
            throw UsuarioYaAsignadoException::enNegocio($actual->nombre);
        }
    }

    public function retirar(User $usuario, Sesion $negocio): void
    {
        $usuario->negocios()->detach($negocio->id);
    }
}
