<?php

namespace App\Exceptions;

use DomainException;

class SinNegocioException extends DomainException
{
    public function __construct()
    {
        parent::__construct('No perteneces a ningún negocio activo. Contacta al administrador.');
    }
}
