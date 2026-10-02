<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\ReporteVentasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReporteVentasServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Factura $factura;

    protected ReporteVentasService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->factura = Factura::create([
            'codigo_factura' => rand(1000, 99999),
            'fecha_emision' => '2026-01-01',
            'total_factura' => 100000,
            'id_usuario' => $this->user->id,
        ]);

        $this->service = new ReporteVentasService;
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

    public function test_reporte_mensual_incluye_todos_los_productos_vendidos_sin_seleccion_previa(): void
    {
        $productoA = $this->createProducto('Producto Alpha');
        $productoB = $this->createProducto('Producto Beta');
        $productoC = $this->createProducto('Producto Gamma Sin Venta');

        $this->createVentaConDetalle($productoA, '2026-05-10', 3);
        $this->createVentaConDetalle($productoB, '2026-05-15', 5);

        $result = $this->service->generateReport(
            period: '2026-05',
            userId: $this->user->id
        );

        $this->assertEquals(ReporteVentasService::STATUS_ALL_SALES, $result['status']);
        $this->assertEquals('Mayo 2026', $result['periodo_label']);
        $this->assertArrayHasKey($productoA->id, $result['productos_con_ventas']);
        $this->assertArrayHasKey($productoB->id, $result['productos_con_ventas']);
        $this->assertArrayNotHasKey($productoC->id, $result['productos_con_ventas']);
        $this->assertEmpty($result['productos_sin_ventas']);
        $this->assertNotNull($result['file_path']);
        $this->assertFileExists($result['file_path']);

        @unlink($result['file_path']);
    }

    public function test_reporte_mensual_retorna_no_sales_cuando_no_hay_ventas(): void
    {
        $result = $this->service->generateReport(
            period: '2026-01',
            userId: $this->user->id
        );

        $this->assertEquals(ReporteVentasService::STATUS_NO_SALES, $result['status']);
        $this->assertEmpty($result['productos_con_ventas']);
        $this->assertNull($result['file_path']);
    }

    public function test_reporte_mensual_permite_filtrar_por_productos_si_se_especifican(): void
    {
        $productoA = $this->createProducto('Producto Filtrado 1');
        $productoB = $this->createProducto('Producto No Seleccionado');

        $this->createVentaConDetalle($productoA, '2026-05-10', 2);
        $this->createVentaConDetalle($productoB, '2026-05-12', 3);

        $result = $this->service->generateReport(
            period: '2026-05',
            userId: $this->user->id,
            productIds: [$productoA->id]
        );

        $this->assertEquals(ReporteVentasService::STATUS_ALL_SALES, $result['status']);
        $this->assertArrayHasKey($productoA->id, $result['productos_con_ventas']);
        $this->assertArrayNotHasKey($productoB->id, $result['productos_con_ventas']);
        $this->assertNotNull($result['file_path']);

        @unlink($result['file_path']);
    }

    public function test_reporte_trimestral_incluye_todos_los_productos_vendidos(): void
    {
        $productoA = $this->createProducto('Laptop Dell');
        $productoB = $this->createProducto('Mouse Logitech');

        $this->createVentaConDetalle($productoA, '2026-04-10', 1);
        $this->createVentaConDetalle($productoB, '2026-05-20', 2);

        $result = $this->service->generateMultiPeriodReport(
            tipo: 'trimestral',
            anio: 2026,
            mesInicio: 4,
            userId: $this->user->id
        );

        $this->assertEquals(ReporteVentasService::STATUS_ALL_SALES, $result['status']);
        $this->assertArrayHasKey($productoA->id, $result['productos_con_ventas']);
        $this->assertArrayHasKey($productoB->id, $result['productos_con_ventas']);
        $this->assertNotNull($result['file_path']);
        $this->assertFileExists($result['file_path']);

        @unlink($result['file_path']);
    }

    public function test_reporte_semestral_incluye_todos_los_productos_vendidos(): void
    {
        $productoA = $this->createProducto('Monitor Samsung');
        $this->createVentaConDetalle($productoA, '2026-02-15', 3);

        $result = $this->service->generateMultiPeriodReport(
            tipo: 'semestral',
            anio: 2026,
            mesInicio: 1,
            userId: $this->user->id
        );

        $this->assertEquals(ReporteVentasService::STATUS_ALL_SALES, $result['status']);
        $this->assertArrayHasKey($productoA->id, $result['productos_con_ventas']);
        $this->assertNotNull($result['file_path']);
        $this->assertFileExists($result['file_path']);

        @unlink($result['file_path']);
    }

    public function test_reporte_anual_incluye_todos_los_productos_vendidos(): void
    {
        $productoA = $this->createProducto('Teclado Mecánico');
        $this->createVentaConDetalle($productoA, '2026-03-01', 4);

        $result = $this->service->generateMultiPeriodReport(
            tipo: 'anual',
            anio: 2026,
            mesInicio: 1,
            userId: $this->user->id
        );

        $this->assertEquals(ReporteVentasService::STATUS_ALL_SALES, $result['status']);
        $this->assertArrayHasKey($productoA->id, $result['productos_con_ventas']);
        $this->assertNotNull($result['file_path']);
        $this->assertFileExists($result['file_path']);

        @unlink($result['file_path']);
    }
}
