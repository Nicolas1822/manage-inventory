<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\ClienteResource;
use App\Filament\Resources\FacturaElectronicaResource;
use App\Filament\Resources\FacturaResource;
use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\ProductoResource;
use App\Filament\Resources\VentaResource;
use App\Filament\Resources\VentaResource\Pages\CreateVenta;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Inventario;
use App\Models\Municipio;
use App\Models\Producto;
use App\Models\Sesion;
use App\Models\Tributo;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\MembresiaNegocio;
use App\Services\NegocioActual;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Criterios de aceptación de la spec negocio-compartido.
 * CA-5 vive en ReporteVentasServiceTest; CA-6, CA-7, CA-8 y CA-16 en MembresiaNegocioTest.
 */
class NegocioCompartidoTest extends TestCase
{
    use DatabaseTransactions;

    private const AVISO = 'No perteneces a ningún negocio';

    private User $a;

    private User $c;

    private User $b;

    private Sesion $n1;

    private Sesion $n2;

    private Tributo $tributo;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();

        // Filament exige FilamentUser fuera de "local"; el panel ya funciona así en desarrollo.
        config(['app.env' => 'local']);

        $this->a = User::factory()->create(['name' => 'Ana Uno']);
        $this->c = User::factory()->create(['name' => 'Carlos Uno']);
        $this->b = User::factory()->create(['name' => 'Beto Dos']);

        $this->n1 = $this->negocio('N1');
        $this->n2 = $this->negocio('N2');
        $membresia = new MembresiaNegocio;
        $membresia->asignar($this->a, $this->n1);
        $membresia->asignar($this->c, $this->n1);
        $membresia->asignar($this->b, $this->n2);

        $this->tributo = Tributo::create(['code' => '01', 'name' => 'IVA', 'description' => 'IVA']);
        $this->municipio = Municipio::create([
            'municipality_code' => '001',
            'municipality_name' => 'Prueba',
            'department_code' => '01',
            'department_name' => 'Prueba',
        ]);
    }

    private function negocio(string $nombre): Sesion
    {
        return Sesion::create(['nombre' => $nombre, 'codigo' => uniqid($nombre), 'estado' => true]);
    }

    private function actuarComo(User $usuario): void
    {
        $this->actingAs($usuario);
        app(NegocioActual::class)->refrescar();
    }

    private function crearProducto(string $nombre, int $cantidad = 10): Producto
    {
        $factura = Factura::create([
            'codigo_factura' => rand(1000, 99999),
            'fecha_emision' => '2026-01-01',
            'total_factura' => 100000,
            'id_usuario' => auth()->id(),
        ]);

        return Producto::create([
            'lote_producto' => rand(100, 999),
            'nombre_producto' => $nombre,
            'precio_unidad' => 5000,
            'marca' => 'Marca Test',
            'cantidad_total_inicial' => $cantidad,
            'cantidad_vendida' => 0,
            'porcentaje_descuento' => 0,
            'porcentaje_impuesto' => '19',
            'codigo_estandar_id' => 1,
            'excluido' => false,
            'estado_disponibilidad' => false,
            'id_factura' => $factura->id,
        ]);
    }

    private function crearCliente(string $nombres): Cliente
    {
        return Cliente::create([
            'id_documento_identificacion' => 3,
            'identificacion' => (string) rand(100000, 999999),
            'nombres' => $nombres,
            'tipo_organizacion' => 2,
            'id_tributo' => $this->tributo->id,
            'id_municipio' => $this->municipio->id,
            'id_usuario' => auth()->id(),
        ]);
    }

    /**
     * Ejecuta la creación real de venta de Filament (con su transacción y bloqueo).
     */
    private function vender(Producto $producto, int $cantidad): Venta
    {
        $metodo = new ReflectionMethod(CreateVenta::class, 'handleRecordCreation');
        $metodo->setAccessible(true);

        return $metodo->invoke(new CreateVenta, [
            'fecha_venta' => now()->toDateString(),
            'total_venta' => $producto->precio_unidad * $cantidad,
            'productos' => [
                ['id_producto' => $producto->id, 'cantidad_vendida_producto' => $cantidad],
            ],
        ]);
    }

    private function stock(Producto $producto): int
    {
        return (int) Inventario::where('id_producto', $producto->id)->value('cantidad_disponible');
    }

    public function test_ca1_un_companero_ve_el_producto_y_su_stock(): void
    {
        $this->actuarComo($this->a);
        $producto = $this->crearProducto('Alfa-N1-ca1', 10);

        $this->actuarComo($this->c);
        $this->assertNotNull(Producto::find($producto->id));
        $this->assertSame(10, $this->stock($producto));
        $this->get(ProductoResource::getUrl('index'))->assertOk()->assertSee('Alfa-N1-ca1');
    }

    public function test_ca2_la_venta_de_un_companero_descuenta_el_stock_y_muestra_su_autor(): void
    {
        $this->actuarComo($this->a);
        $producto = $this->crearProducto('Alfa-N1-ca2', 10);

        $this->actuarComo($this->c);
        $venta = $this->vender($producto, 3);
        $this->assertSame($this->n1->id, $venta->id_sesion);

        $this->actuarComo($this->a);
        $this->assertSame(7, $this->stock($producto));
        $this->assertNotNull(Venta::find($venta->id));
        $this->get(VentaResource::getUrl('index'))->assertOk()->assertSee('Carlos Uno');
    }

    public function test_ca3_otro_negocio_no_ve_nada_en_listados_ni_dashboard(): void
    {
        $this->actuarComo($this->a);
        $producto = $this->crearProducto('Alfa-N1-ca3', 10);
        $this->crearCliente('Cliente-N1-ca3');
        $this->vender($producto, 1);

        $this->actuarComo($this->b);
        $this->assertSame(0, Producto::count());
        $this->assertSame(0, Venta::count());
        $this->assertSame(0, VentaDetalle::count());
        $this->assertSame(0, Cliente::count());
        $this->assertSame(0, Factura::count());

        foreach ([
            ProductoResource::getUrl('index'),
            InventarioResource::getUrl('index'),
            VentaResource::getUrl('index'),
            ClienteResource::getUrl('index'),
            FacturaResource::getUrl('index'),
            FacturaElectronicaResource::getUrl('index'),
            Dashboard::getUrl(),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('Alfa-N1-ca3')
                ->assertDontSee('Cliente-N1-ca3')
                ->assertDontSee('Ana Uno');
        }
    }

    public function test_ca4_url_directa_a_registro_ajeno_da_404(): void
    {
        $this->actuarComo($this->a);
        $producto = $this->crearProducto('Alfa-N1-ca4', 10);
        $cliente = $this->crearCliente('Cliente-N1-ca4');
        $venta = $this->vender($producto, 1);

        $this->actuarComo($this->b);
        $this->get(ProductoResource::getUrl('edit', ['record' => $producto]))->assertNotFound();
        $this->get(ClienteResource::getUrl('edit', ['record' => $cliente]))->assertNotFound();
        $this->get(VentaResource::getUrl('edit', ['record' => $venta]))->assertNotFound();
    }

    public function test_ca9_usuario_sin_negocio_ve_aviso_y_no_puede_crear(): void
    {
        $this->actuarComo($this->a);
        $this->crearProducto('Alfa-N1-ca9', 10);

        $this->actuarComo(User::factory()->create());
        $this->get(Dashboard::getUrl())->assertOk()->assertSee(self::AVISO);
        $this->assertSame(0, Producto::count());
        $this->assertFalse(ProductoResource::canCreate());
        $this->get(ProductoResource::getUrl('create'))->assertForbidden();
    }

    public function test_ca10_superadmin_sin_negocio_no_ve_ni_crea_datos(): void
    {
        $this->actuarComo($this->a);
        $this->crearProducto('Alfa-N1-ca10', 10);
        $this->actuarComo($this->b);
        $this->crearProducto('Beta-N2-ca10', 10);

        $superadmin = User::factory()->create();
        $superadmin->forceFill(['es_superadmin' => true])->save();
        $this->actuarComo($superadmin);

        $this->assertSame(0, Producto::count());
        $this->assertSame(0, Inventario::count());
        $this->assertFalse(VentaResource::canCreate());
        $this->get(ProductoResource::getUrl('index'))->assertOk()
            ->assertDontSee('Alfa-N1-ca10')
            ->assertDontSee('Beta-N2-ca10');
    }

    public function test_ca11_negocio_desactivado_se_comporta_como_sin_negocio(): void
    {
        $this->actuarComo($this->a);
        $this->crearProducto('Alfa-N1-ca11', 10);

        $this->n1->update(['estado' => false]);
        $this->actuarComo($this->a);

        $this->get(Dashboard::getUrl())->assertOk()->assertSee(self::AVISO);
        $this->assertSame(0, Producto::count());
        $this->assertFalse(ProductoResource::canCreate());
    }

    public function test_ca12_usuario_retirado_pierde_acceso_y_sus_ventas_conservan_autor(): void
    {
        $this->actuarComo($this->a);
        $producto = $this->crearProducto('Alfa-N1-ca12', 10);
        $venta = $this->vender($producto, 2);

        (new MembresiaNegocio)->retirar($this->a, $this->n1);

        $this->actuarComo($this->a);
        $this->assertSame(0, Venta::count());

        $this->actuarComo($this->c);
        $detalle = VentaDetalle::where('id_venta', $venta->id)->firstOrFail();
        $this->assertSame('Ana Uno', $detalle->users->name);
        $this->get(VentaResource::getUrl('index'))->assertOk()->assertSee('Ana Uno');
    }

    public function test_ca13_productos_homonimos_de_dos_negocios_son_independientes(): void
    {
        $this->actuarComo($this->a);
        $productoN1 = $this->crearProducto('Mismo nombre', 10);
        $this->actuarComo($this->b);
        $productoN2 = $this->crearProducto('Mismo nombre', 10);

        $this->actuarComo($this->a);
        $productoN1->update(['precio_unidad' => 9999]);
        $this->vender($productoN1, 4);

        $this->actuarComo($this->b);
        $this->assertSame(5000, (int) $productoN2->fresh()->precio_unidad);
        $this->assertSame(10, $this->stock($productoN2));
    }

    public function test_ca15_un_companero_edita_y_borra_lo_creado_por_otro(): void
    {
        $this->actuarComo($this->a);
        $producto = $this->crearProducto('Alfa-N1-ca15', 10);
        $cliente = $this->crearCliente('Cliente-N1-ca15');

        $this->actuarComo($this->c);
        Producto::findOrFail($producto->id)->update(['precio_unidad' => 7000]);
        Cliente::findOrFail($cliente->id)->delete();

        $this->actuarComo($this->a);
        $this->assertSame(7000, (int) Producto::findOrFail($producto->id)->precio_unidad);
        $this->assertNull(Cliente::find($cliente->id));
        $this->get(ClienteResource::getUrl('index'))->assertOk()->assertDontSee('Cliente-N1-ca15');
    }

    public function test_ca15_un_companero_ve_los_clientes_creados_por_otro(): void
    {
        $this->actuarComo($this->a);
        $this->crearCliente('Cliente-N1-visible');

        $this->actuarComo($this->c);
        $this->get(ClienteResource::getUrl('index'))->assertOk()->assertSee('Cliente-N1-visible');
    }

    public function test_ca17_superadmin_miembro_ve_solo_su_negocio(): void
    {
        $this->actuarComo($this->a);
        $this->crearProducto('Alfa-N1-ca17', 10);
        $this->actuarComo($this->b);
        $this->crearProducto('Beta-N2-ca17', 10);

        $superadmin = User::factory()->create();
        $superadmin->forceFill(['es_superadmin' => true])->save();
        (new MembresiaNegocio)->asignar($superadmin, $this->n1);
        $this->actuarComo($superadmin);

        $this->get(ProductoResource::getUrl('index'))->assertOk()
            ->assertSee('Alfa-N1-ca17')
            ->assertDontSee('Beta-N2-ca17');
    }
}
