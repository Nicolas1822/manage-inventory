<?php

namespace Tests\Feature;

use App\Exceptions\SinNegocioException;
use App\Models\Factura;
use App\Models\Sesion;
use App\Models\User;
use App\Services\NegocioActual;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NegocioScopeTest extends TestCase
{
    use DatabaseTransactions;

    private function negocioCon(User $usuario, string $nombre): Sesion
    {
        $negocio = Sesion::create(['nombre' => $nombre, 'codigo' => uniqid($nombre), 'estado' => true]);
        $negocio->usuarios()->attach($usuario->id);

        return $negocio;
    }

    private function actuarComo(User $usuario): void
    {
        $this->actingAs($usuario);
        app(NegocioActual::class)->refrescar();
    }

    private function crearFactura(int $codigo): Factura
    {
        return Factura::create([
            'codigo_factura' => $codigo,
            'fecha_emision' => now()->toDateString(),
            'total_factura' => 1000,
            'id_usuario' => auth()->id(),
        ]);
    }

    public function test_un_negocio_no_ve_los_registros_de_otro(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $n1 = $this->negocioCon($a, 'N1');
        $this->negocioCon($b, 'N2');

        $this->actuarComo($a);
        $factura = $this->crearFactura(111);
        $this->assertSame($n1->id, $factura->id_sesion);

        $this->actuarComo($b);
        $this->assertNull(Factura::find($factura->id));
        $this->assertSame(0, Factura::count());
    }

    public function test_el_registro_va_al_negocio_activo_aunque_llegue_otro_id_sesion(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $n1 = $this->negocioCon($a, 'N1');
        $n2 = $this->negocioCon($b, 'N2');

        $this->actuarComo($a);
        $factura = Factura::create([
            'id_sesion' => $n2->id,
            'codigo_factura' => 222,
            'fecha_emision' => now()->toDateString(),
            'total_factura' => 1000,
            'id_usuario' => $a->id,
        ]);

        $this->assertSame($n1->id, $factura->id_sesion);
    }

    public function test_usuario_sin_negocio_ve_vacio_y_no_puede_crear(): void
    {
        $a = User::factory()->create();
        $this->negocioCon($a, 'N1');
        $this->actuarComo($a);
        $this->crearFactura(333);

        $sinNegocio = User::factory()->create();
        $this->actuarComo($sinNegocio);

        $this->assertSame(0, Factura::count());
        $this->expectException(SinNegocioException::class);
        $this->crearFactura(444);
    }

    public function test_negocio_inactivo_se_trata_como_sin_negocio(): void
    {
        $a = User::factory()->create();
        $n1 = $this->negocioCon($a, 'N1');
        $this->actuarComo($a);
        $this->crearFactura(555);

        $n1->update(['estado' => false]);
        app(NegocioActual::class)->refrescar();

        $this->assertFalse(app(NegocioActual::class)->tieneNegocio());
        $this->assertSame(0, Factura::count());
    }
}
