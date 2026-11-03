<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resuelve el negocio (sesion) activo del usuario autenticado.
 *
 * Se registra como instancia "scoped": vive una petición, así que un cambio
 * de membresía se ve en la siguiente.
 */
class NegocioActual
{
    private bool $resuelto = false;

    private ?int $id = null;

    public function hayUsuario(): bool
    {
        return Auth::check();
    }

    public function esSuperadmin(): bool
    {
        $usuario = Auth::user();

        return $usuario instanceof User && $usuario->es_superadmin;
    }

    /**
     * Id del negocio activo; null sin usuario, sin negocio o con negocio inactivo.
     */
    public function id(): ?int
    {
        if (! $this->resuelto) {
            $this->id = $this->resolver();
            $this->resuelto = true;
        }

        return $this->id;
    }

    public function tieneNegocio(): bool
    {
        return $this->id() !== null;
    }

    public function refrescar(): void
    {
        $this->resuelto = false;
        $this->id = null;
    }

    private function resolver(): ?int
    {
        $usuario = Auth::user();

        if (! $usuario instanceof User) {
            return null;
        }

        $id = $usuario->negocios()->where('sesion.estado', true)->value('sesion.id');

        return $id === null ? null : (int) $id;
    }
}
