<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventarioApiController extends Controller
{
    /**
     * Consulta el listado de inventario con filtros de búsqueda, bajo stock y disponibilidad.
     */
    public function index(Request $request): JsonResponse
    {
        $buscar = $request->query('buscar', $request->query('q'));
        $bajoStock = $request->boolean('bajo_stock');
        $agotado = $request->boolean('agotado');
        $disponible = $request->boolean('disponible');
        $umbral = (int) $request->query('umbral', 5);
        $limite = min(max((int) $request->query('limite', 10), 1), 50);

        $query = Inventario::with(['producto.unidadDeMedida']);

        if (! empty($buscar)) {
            $termino = trim((string) $buscar);
            $query->whereHas('producto', function (Builder $q) use ($termino) {
                $q->where('nombre_producto', 'like', "%{$termino}%")
                    ->orWhere('marca', 'like', "%{$termino}%")
                    ->orWhere('lote_producto', 'like', "%{$termino}%");
            });
        }

        if ($bajoStock) {
            $query->whereBetween('cantidad_disponible', [1, $umbral]);
        }

        if ($agotado) {
            $query->where('cantidad_disponible', 0);
        } elseif ($disponible) {
            $query->where('cantidad_disponible', '>', 0);
        }

        $inventarios = $query->orderBy('cantidad_disponible', 'asc')->limit($limite)->get();

        $data = $inventarios->map(fn (Inventario $item): array => $this->formatInventarioItem($item));

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'filtros' => [
                'buscar' => $buscar,
                'bajo_stock' => $bajoStock,
                'agotado' => $agotado,
                'disponible' => $disponible,
            ],
            'data' => $data,
        ]);
    }

    /**
     * Consulta el stock y detalle de un producto específico por ID de inventario o ID de producto.
     */
    public function show(string $id): JsonResponse
    {
        $item = Inventario::with(['producto.unidadDeMedida'])
            ->where('id', $id)
            ->orWhere('id_producto', $id)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Producto o inventario no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatInventarioItem($item),
        ]);
    }

    /**
     * Obtiene un resumen consolidado del estado actual del inventario.
     */
    public function resumen(Request $request): JsonResponse
    {
        $umbral = (int) $request->query('umbral', 5);

        $totalProductos = Inventario::count();
        $totalAgotados = Inventario::where('cantidad_disponible', 0)->count();
        $totalBajoStock = Inventario::whereBetween('cantidad_disponible', [1, $umbral])->count();
        $totalDisponibles = Inventario::where('cantidad_disponible', '>', 0)->count();

        $textoResumen = "📊 *Resumen de Inventario*\n\n"
          ."📦 Total productos: {$totalProductos}\n"
          ."✅ Disponibles: {$totalDisponibles}\n"
          ."⚠️ Por agotarse (<= {$umbral}): {$totalBajoStock}\n"
          ."❌ Agotados: {$totalAgotados}";

        return response()->json([
            'success' => true,
            'data' => [
                'total_productos' => $totalProductos,
                'total_disponibles' => $totalDisponibles,
                'total_bajo_stock' => $totalBajoStock,
                'total_agotados' => $totalAgotados,
                'umbral_bajo_stock' => $umbral,
                'texto_whatsapp' => $textoResumen,
            ],
        ]);
    }

    /**
     * Consulta los productos más vendidos en un mes específico.
     * Si no se envía el año, toma por defecto el año actual.
     */
    public function masVendidos(Request $request): JsonResponse
    {
        $mesInput = $request->query('mes');
        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        if (empty($mesInput)) {
            $mes = (int) date('n');
        } elseif (is_numeric($mesInput)) {
            $mes = (int) $mesInput;
        } else {
            $mesTexto = mb_strtolower(trim((string) $mesInput));
            $mesesMap = [
                'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
                'noviembre' => 11, 'diciembre' => 12,
            ];
            $mes = $mesesMap[$mesTexto] ?? null;
        }

        if (! $mes || $mes < 1 || $mes > 12) {
            return response()->json([
                'success' => false,
                'message' => 'El mes proporcionado no es válido. Debe ser un número del 1 al 12 o el nombre del mes.',
            ], 422);
        }

        $anioInput = $request->query('anio', $request->query('ano', $request->query('year')));
        $anio = ! empty($anioInput) && is_numeric($anioInput) ? (int) $anioInput : (int) date('Y');

        $limite = min(max((int) $request->query('limite', $request->query('limit', 10)), 1), 50);
        $usuarioId = $request->query('id_usuario', $request->query('usuario_id'));

        $query = Producto::query()
            ->select('producto.id', 'producto.nombre_producto', 'producto.marca', 'producto.precio_unidad')
            ->join('venta_detalle', 'producto.id', '=', 'venta_detalle.id_producto')
            ->join('venta', 'venta_detalle.id_venta', '=', 'venta.id')
            ->whereMonth('venta.fecha_venta', $mes)
            ->whereYear('venta.fecha_venta', $anio)
            ->selectRaw('SUM(CAST(venta_detalle.cantidad_vendida_producto AS UNSIGNED)) as total_vendido')
            ->selectRaw('SUM(CAST(venta_detalle.cantidad_vendida_producto AS UNSIGNED) * producto.precio_unidad) as monto_total')
            ->groupBy('producto.id', 'producto.nombre_producto', 'producto.marca', 'producto.precio_unidad')
            ->orderByDesc('total_vendido');

        if (! empty($usuarioId) && is_numeric($usuarioId)) {
            $query->where('venta_detalle.id_usuario', (int) $usuarioId);
        }

        $productos = $query->limit($limite)->get();

        $mesNombre = $mesesNombres[$mes];
        $totalUnidadesVendidas = 0;
        $montoTotalPeriodo = 0;

        $data = $productos->map(function ($producto, int $index) use (&$totalUnidadesVendidas, &$montoTotalPeriodo): array {
            $totalVendido = (int) $producto->total_vendido;
            $precioUnidad = (int) $producto->precio_unidad;
            $montoTotal = (int) $producto->monto_total;

            $totalUnidadesVendidas += $totalVendido;
            $montoTotalPeriodo += $montoTotal;

            return [
                'posicion' => $index + 1,
                'id_producto' => $producto->id,
                'nombre_producto' => $producto->nombre_producto,
                'marca' => $producto->marca ?? 'N/A',
                'precio_unidad' => $precioUnidad,
                'precio_formateado' => '$'.number_format($precioUnidad, 0, ',', '.'),
                'total_vendido' => $totalVendido,
                'monto_total' => $montoTotal,
                'monto_total_formateado' => '$'.number_format($montoTotal, 0, ',', '.'),
            ];
        });

        $lineasWhatsapp = ["🏆 *Top Productos Más Vendidos - {$mesNombre} {$anio}*\n"];
        if ($data->isEmpty()) {
            $lineasWhatsapp[] = 'No se registraron ventas en este periodo.';
        } else {
            foreach ($data as $item) {
                $lineasWhatsapp[] = "{$item['posicion']}. *{$item['nombre_producto']}* ({$item['marca']})\n"
                    ."   🔹 Vendidos: {$item['total_vendido']} unds\n"
                    ."   💰 Total: {$item['monto_total_formateado']}";
            }
            $lineasWhatsapp[] = "\n📊 *Consolidado:*";
            $lineasWhatsapp[] = "📦 Total Unidades: {$totalUnidadesVendidas}";
            $lineasWhatsapp[] = '💵 Recaudo: $'.number_format($montoTotalPeriodo, 0, ',', '.');
        }
        $textoWhatsapp = implode("\n", $lineasWhatsapp);

        return response()->json([
            'success' => true,
            'periodo' => [
                'mes' => $mes,
                'mes_nombre' => $mesNombre,
                'anio' => $anio,
                'etiqueta' => "{$mesNombre} {$anio}",
            ],
            'total_productos' => $data->count(),
            'resumen' => [
                'total_unidades' => $totalUnidadesVendidas,
                'monto_total' => $montoTotalPeriodo,
                'monto_total_formateado' => '$'.number_format($montoTotalPeriodo, 0, ',', '.'),
            ],
            'data' => $data,
            'texto_whatsapp' => $textoWhatsapp,
        ]);
    }

    /**
     * Da formato estructurado y mensaje para WhatsApp a un elemento de inventario.
     *
     * @return array<string, mixed>
     */
    private function formatInventarioItem(Inventario $item): array
    {
        $producto = $item->producto;
        $cantidad = (int) $item->cantidad_disponible;
        $precio = (int) ($producto?->precio_unidad ?? 0);
        $unidad = $producto?->unidadDeMedida?->name ?? 'Unidades';

        $estado = 'Disponible';
        if ($cantidad === 0) {
            $estado = 'Agotado';
        } elseif ($cantidad <= 5) {
            $estado = 'Bajo stock';
        }

        $precioFormateado = '$'.number_format($precio, 0, ',', '.');
        $nombre = $producto?->nombre_producto ?? 'Sin nombre';
        $marca = $producto?->marca ?? 'N/A';

        $textoWhatsapp = "📦 *{$nombre}* ({$marca})\n"
          ."🔹 *Stock disponible:* {$cantidad} {$unidad} ({$estado})\n"
          ."💰 *Precio:* {$precioFormateado}";

        return [
            'id' => $item->id,
            'id_producto' => $item->id_producto,
            'nombre' => $nombre,
            'marca' => $marca,
            'lote' => $producto?->lote_producto,
            'cantidad_disponible' => $cantidad,
            'unidad_medida' => $unidad,
            'precio_unidad' => $precio,
            'precio_formateado' => $precioFormateado,
            'estado' => $estado,
            'agotado' => $cantidad === 0,
            'bajo_stock' => $cantidad > 0 && $cantidad <= 5,
            'texto_whatsapp' => $textoWhatsapp,
        ];
    }
}
