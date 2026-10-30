<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\Producto;
use App\Models\Sesion;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MasVendidosApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Sesion $negocio;

    protected Factura $factura;

    protected array $headers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->negocio = Sesion::create(['nombre' => 'Negocio de prueba', 'codigo' => uniqid('test-'), 'estado' => true]);
        $this->negocio->usuarios()->attach($this->user->id);
        $this->actingAs($this->user);

        $apiKey = config('services.n8n.api_key', env('N8N_API_KEY'));
        if (! empty($apiKey)) {
            $this->headers = ['X-API-KEY' => $apiKey];
        }

        $this->factura = Factura::create([
            'codigo_factura' => rand(1000, 99999),
            'fecha_emision' => '2026-01-01',
            'total_factura' => 100000,
            'id_usuario' => $this->user->id,
        ]);
    }

    protected function createProducto(string $nombre, int $precio = 5000): Producto
    {
        return Producto::create([
            'lote_producto' => rand(100, 999),
            'nombre_producto' => $nombre,
            'precio_unidad' => $precio,
            'marca' => 'Marca Test',
            'cantidad_total_inicial' => 100,
            'cantidad_vendida' => 0,
            'porcentaje_descuento' => 0,
            'porcentaje_impuesto' => '19',
            'codigo_estandar_id' => 1,
            'excluido' => false,
            'estado_disponibilidad' => false,
            'id_factura' => $this->factura->id,
        ]);
    }

    protected function createVentaConDetalle(
        Producto $producto,
        string $fechaVenta,
        int $cantidad = 2
    ): VentaDetalle {
        $venta = Venta::create([
            'codigo_venta' => rand(1000, 99999),
            'fecha_venta' => $fechaVenta,
            'total_venta' => $producto->precio_unidad * $cantidad,
            'estado_factura_electronica' => false,
        ]);

        return VentaDetalle::create([
            'id_venta' => $venta->id,
            'id_producto' => $producto->id,
            'id_usuario' => $this->user->id,
            'cantidad_vendida_producto' => $cantidad,
        ]);
    }

    public function test_toma_el_ano_actual_por_defecto_si_no_se_envia(): void
    {
        $currentYear = (int) date('Y');
        $productoA = $this->createProducto('Producto Test Año Actual', 10000);
        $this->createVentaConDetalle($productoA, "{$currentYear}-06-15", 4);

        $response = $this->getJson('/api/inventario/mas-vendidos?mes=6', $this->headers);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'periodo' => [
                'mes' => 6,
                'mes_nombre' => 'Junio',
                'anio' => $currentYear,
            ],
        ]);
        $response->assertJsonFragment([
            'nombre_producto' => 'Producto Test Año Actual',
            'total_vendido' => 4,
            'monto_total' => 40000,
        ]);
    }

    public function test_respeta_el_ano_especificado_si_se_envia(): void
    {
        $producto2024 = $this->createProducto('Producto 2024', 20000);
        $this->createVentaConDetalle($producto2024, '2024-03-20', 5);

        // Si consultamos 2024 explícitamente
        $response = $this->getJson('/api/inventario/mas-vendidos?mes=3&anio=2024', $this->headers);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'periodo' => [
                'mes' => 3,
                'mes_nombre' => 'Marzo',
                'anio' => 2024,
            ],
        ]);
        $response->assertJsonFragment([
            'nombre_producto' => 'Producto 2024',
            'total_vendido' => 5,
            'monto_total' => 100000,
        ]);
    }

    public function test_permite_consultar_por_nombre_de_mes(): void
    {
        $currentYear = (int) date('Y');
        $producto = $this->createProducto('Producto Octubre', 8000);
        $this->createVentaConDetalle($producto, "{$currentYear}-10-05", 3);

        $response = $this->getJson('/api/ventas/mas-vendidos?mes=octubre', $this->headers);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'periodo' => [
                'mes' => 10,
                'mes_nombre' => 'Octubre',
                'anio' => $currentYear,
            ],
        ]);
        $response->assertJsonFragment([
            'nombre_producto' => 'Producto Octubre',
            'total_vendido' => 3,
        ]);
    }

    public function test_retorna_422_si_el_mes_es_invalido(): void
    {
        $response = $this->getJson('/api/inventario/mas-vendidos?mes=invalido', $this->headers);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_ordena_correctamente_por_total_vendido_descendente(): void
    {
        $currentYear = (int) date('Y');
        $productoPoco = $this->createProducto('Producto Menor Venta', 5000);
        $productoMucho = $this->createProducto('Producto Mayor Venta', 5000);

        $this->createVentaConDetalle($productoPoco, "{$currentYear}-07-01", 2);
        $this->createVentaConDetalle($productoMucho, "{$currentYear}-07-02", 15);

        $response = $this->getJson('/api/inventario/mas-vendidos?mes=7', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals('Producto Mayor Venta', $data[0]['nombre_producto']);
        $this->assertEquals(15, $data[0]['total_vendido']);
    }

    public function test_retorna_401_si_api_key_es_invalida_o_ausente(): void
    {
        $apiKey = config('services.n8n.api_key', env('N8N_API_KEY'));
        if (empty($apiKey)) {
            $this->markTestSkipped('N8N_API_KEY no está configurada.');
        }

        $response = $this->getJson('/api/inventario/mas-vendidos?mes=5', ['X-API-KEY' => 'clave_incorrecta']);
        $response->assertStatus(401);
    }
}
