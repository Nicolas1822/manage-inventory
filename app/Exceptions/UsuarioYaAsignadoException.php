<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * El usuario ya pertenece a otro negocio; en este ciclo solo puede tener uno.
 */
class UsuarioYaAsignadoException extends RuntimeException
{
    public static function enNegocio(string $nombreNegocio): self
    {
        return new self("El usuario ya pertenece al negocio «{$nombreNegocio}».");
    }
}
