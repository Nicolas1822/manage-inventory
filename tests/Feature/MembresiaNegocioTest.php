<?php

namespace Tests\Feature;

use App\Exceptions\UsuarioYaAsignadoException;
use App\Filament\Resources\SesionResource;
use App\Filament\Resources\UserResource;
use App\Models\Sesion;
use App\Models\User;
use App\Services\MembresiaNegocio;
use App\Services\NegocioActual;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MembresiaNegocioTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Filament exige FilamentUser fuera de "local"; el panel ya funciona así en desarrollo.
        config(['app.env' => 'local']);
    }

    private function negocio(string $nombre): Sesion
    {
        return Sesion::create(['nombre' => $nombre, 'codigo' => uniqid('c'), 'estado' => true]);
    }

    private function actuarComo(User $usuario): void
    {
        $this->actingAs($usuario);
        app(NegocioActual::class)->refrescar();
    }

    private function superadmin(): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['es_superadmin' => true])->save();

        return $usuario;
    }

    public function test_asignar_a_otro_negocio_lanza_con_el_nombre_del_actual(): void
    {
        $usuario = User::factory()->create();
        $servicio = app(MembresiaNegocio::class);
        $n1 = $this->negocio('Negocio Uno');
        $n2 = $this->negocio('Negocio Dos');
        $servicio->asignar($usuario, $n1);

        try {
            $servicio->asignar($usuario, $n2);
            $this->fail('Debía lanzar UsuarioYaAsignadoException.');
        } catch (UsuarioYaAsignadoException $e) {
            $this->assertStringContainsString('«Negocio Uno»', $e->getMessage());
        }

        $this->assertSame([$n1->id], $usuario->negocios()->pluck('sesion.id')->all());
    }

    public function test_asignar_dos_veces_al_mismo_negocio_es_idempotente(): void
    {
        $usuario = User::factory()->create();
        $n1 = $this->negocio('N1');
        $servicio = app(MembresiaNegocio::class);

        $servicio->asignar($usuario, $n1);
        $servicio->asignar($usuario, $n1);

        $this->assertSame(1, $usuario->negocios()->count());
    }

    public function test_retirar_quita_al_usuario_y_permite_reasignar(): void
    {
        $usuario = User::factory()->create();
        $n1 = $this->negocio('N1');
        $n2 = $this->negocio('N2');
        $servicio = app(MembresiaNegocio::class);

        $servicio->asignar($usuario, $n1);
        $servicio->retirar($usuario, $n1);
        $this->assertSame(0, $usuario->negocios()->count());

        $servicio->asignar($usuario, $n2);
        $this->assertSame(1, $usuario->negocios()->count());
    }

    public function test_superadmin_accede_a_negocios_y_usuarios(): void
    {
        $this->actuarComo($this->superadmin());

        $this->get(SesionResource::getUrl('index'))->assertOk();
        $this->get(UserResource::getUrl('index'))->assertOk();
    }

    public function test_usuario_normal_recibe_403_en_negocios_y_usuarios(): void
    {
        $this->actuarComo(User::factory()->create());

        $this->get(SesionResource::getUrl('index'))->assertForbidden();
        $this->get(SesionResource::getUrl('create'))->assertForbidden();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(UserResource::getUrl('create'))->assertForbidden();
    }

    public function test_es_superadmin_no_es_asignable_en_masa(): void
    {
        $creado = User::create([
            'name' => 'X',
            'email' => uniqid().'@example.com',
            'password' => 'secreto123',
            'es_superadmin' => true,
        ]);
        $this->assertFalse((bool) $creado->fresh()->es_superadmin);

        $usuario = User::factory()->create();
        $usuario->fill(['es_superadmin' => true])->save();
        $this->assertFalse((bool) $usuario->fresh()->es_superadmin);
    }

    public function test_el_formulario_de_usuarios_no_tiene_campo_superadmin(): void
    {
        $this->actuarComo($this->superadmin());

        $this->get(UserResource::getUrl('create'))
            ->assertOk()
            ->assertDontSee('es_superadmin');
    }
}
