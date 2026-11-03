<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class ReporteVentasService
{
    public const STATUS_NO_SALES = 'no_sales';

    public const STATUS_PARTIAL_SALES = 'partial_sales';

    public const STATUS_ALL_SALES = 'all_sales';

    public const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * Obtiene las opciones de periodo mensual en formato ['Y-m' => 'Mes Año']
     */
    public static function getPeriodOptions(?int $sesionId = null): array
    {
        $options = [];
        $now = now();
        $startYear = max(2023, $now->year - 3);
        $endYear = $now->year;

        $minVentaDate = Venta::query()
            ->when($sesionId !== null, fn ($q) => $q->where('venta.id_sesion', $sesionId))
            ->min('fecha_venta');
        if ($minVentaDate) {
            $minYear = (int) substr($minVentaDate, 0, 4);
            if ($minYear < $startYear) {
                $startYear = $minYear;
            }
        }

        for ($year = $endYear; $year >= $startYear; $year--) {
            $maxMonth = ($year === $now->year) ? $now->month : 12;
            for ($month = $maxMonth; $month >= 1; $month--) {
                $key = sprintf('%04d-%02d', $year, $month);
                $options[$key] = self::MESES[$month].' '.$year;
            }
        }

        return $options;
    }

    /**
     * Obtiene los productos disponibles en el negocio indicado
     */
    public static function getProductsOptions(int $sesionId): array
    {
        return Producto::query()
            ->join('inventario', 'producto.id', '=', 'inventario.id_producto')
            ->where('producto.id_sesion', $sesionId)
            ->where('inventario.id_sesion', $sesionId)
            ->orderBy('producto.nombre_producto')
            ->pluck('producto.nombre_producto', 'producto.id')
            ->toArray();
    }

    /**
     * Genera el reporte de ventas por producto en el periodo indicado
     *
     * @param  string  $period  Formato 'Y-m' (ej: '2026-09')
     * @param  int  $sesionId  ID del negocio (sesion) cuyas ventas se reportan
     * @param  array<int>|null  $productIds  Lista opcional de IDs de productos seleccionados
     * @return array Resumen con estado, nombres, productos sin ventas y ruta del archivo si aplica
     */
    public function generateReport(string $period, int $sesionId, ?array $productIds = null): array
    {
        $carbon = Carbon::createFromFormat('Y-m', $period);
        $startDate = $carbon->copy()->startOfMonth()->toDateString();
        $endDate = $carbon->copy()->endOfMonth()->toDateString();

        $mesNumero = (int) $carbon->format('n');
        $mesNombre = self::MESES[$mesNumero];
        $mesLower = strtolower($mesNombre);
        $anio = (int) $carbon->format('Y');

        $query = VentaDetalle::query()
            ->where('venta_detalle.id_sesion', $sesionId)
            ->whereHas('venta', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('fecha_venta', [$startDate, $endDate]);
            })
            ->with(['venta', 'producto']);

        if (! empty($productIds)) {
            $query->whereIn('id_producto', $productIds);
        }

        $ventasDetalle = $query->get();

        if ($ventasDetalle->isEmpty()) {
            $productosSinVentas = ! empty($productIds)
                ? array_values(Producto::query()->where('id_sesion', $sesionId)->whereIn('id', $productIds)->pluck('nombre_producto')->toArray())
                : [];

            return [
                'status' => self::STATUS_NO_SALES,
                'mes_nombre' => $mesNombre,
                'anio' => $anio,
                'periodo_label' => "{$mesNombre} {$anio}",
                'productos_con_ventas' => [],
                'productos_sin_ventas' => $productosSinVentas,
                'file_path' => null,
                'file_name' => null,
            ];
        }

        $productIdsWithSales = $ventasDetalle->pluck('id_producto')->unique()->toArray();
        $productosConVentas = Producto::query()
            ->where('id_sesion', $sesionId)
            ->whereIn('id', $productIdsWithSales)
            ->pluck('nombre_producto', 'id')
            ->toArray();

        $productosSinVentas = [];
        if (! empty($productIds)) {
            $selectedProducts = Producto::query()
                ->where('id_sesion', $sesionId)
                ->whereIn('id', $productIds)
                ->pluck('nombre_producto', 'id')
                ->toArray();

            foreach ($selectedProducts as $id => $nombre) {
                if (! in_array($id, $productIdsWithSales)) {
                    $productosSinVentas[] = $nombre;
                }
            }
        }

        $status = empty($productosSinVentas) ? self::STATUS_ALL_SALES : self::STATUS_PARTIAL_SALES;

        $fileName = "reporte_ventas_{$mesLower}_{$anio}.xlsx";
        $filePath = $this->createExcelFile($ventasDetalle, $fileName);

        return [
            'status' => $status,
            'mes_nombre' => $mesNombre,
            'anio' => $anio,
            'periodo_label' => "{$mesNombre} {$anio}",
            'productos_con_ventas' => $productosConVentas,
            'productos_sin_ventas' => $productosSinVentas,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ];
    }

    /**
     * Crea el archivo XLSX en disco usando OpenSpout
     */
    protected function createExcelFile(Collection $ventasDetalle, string $fileName): string
    {
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFilePath = $tempDir.DIRECTORY_SEPARATOR.uniqid('reporte_ventas_', true).'.xlsx';

        $options = new Options;
        $options->setColumnWidth(18, 1); // Código de Venta
        $options->setColumnWidth(16, 2); // Fecha de Venta
        $options->setColumnWidth(35, 3); // Producto
        $options->setColumnWidth(14, 4); // Lote
        $options->setColumnWidth(18, 5); // Cantidad Vendida
        $options->setColumnWidth(18, 6); // Precio Unitario
        $options->setColumnWidth(18, 7); // Subtotal

        $writer = new Writer($options);
        $writer->openToFile($tempFilePath);

        $headerStyle = (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('E2E8F0'));

        $headers = [
            'Código de Venta',
            'Fecha de Venta',
            'Producto',
            'Lote',
            'Cantidad Vendida',
            'Precio Unitario',
            'Subtotal',
        ];

        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $dataStyle = (new Style)
            ->setFontSize(11)
            ->setFontName('Calibri');

        $subtotalStyle = (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('F1F5F9'));

        $granTotalStyle = (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('CBD5E1'));

        $ventasPorProducto = $ventasDetalle
            ->sortBy([
                ['producto.nombre_producto', 'asc'],
                ['venta.fecha_venta', 'asc'],
            ])
            ->groupBy('id_producto');

        $granTotalCantidad = 0;
        $granTotalSubtotal = 0.0;

        foreach ($ventasPorProducto as $detallesDelProducto) {
            $subtotalProductoCantidad = 0;
            $subtotalProductoMonto = 0.0;
            $nombreProducto = $detallesDelProducto->first()->producto?->nombre_producto ?? 'Producto';

            foreach ($detallesDelProducto as $detalle) {
                $codigoVenta = $detalle->venta?->codigo_venta ?? '';
                $fechaVenta = $detalle->venta?->fecha_venta ?? '';
                $productoNombre = $detalle->producto?->nombre_producto ?? '';
                $lote = $detalle->producto?->lote_producto ?? '';
                $cantidad = (int) ($detalle->cantidad_vendida_producto ?? 0);
                $precioUnitario = (float) ($detalle->producto?->precio_unidad ?? 0);
                $subtotal = $cantidad * $precioUnitario;

                $subtotalProductoCantidad += $cantidad;
                $subtotalProductoMonto += $subtotal;

                $rowValues = [
                    $codigoVenta,
                    $fechaVenta,
                    $productoNombre,
                    $lote,
                    $cantidad,
                    $precioUnitario,
                    $subtotal,
                ];

                $writer->addRow(Row::fromValues($rowValues, $dataStyle));
            }

            $subtotalRow = [
                '',
                '',
                "TOTAL {$nombreProducto}",
                '',
                $subtotalProductoCantidad,
                '',
                $subtotalProductoMonto,
            ];
            $writer->addRow(Row::fromValues($subtotalRow, $subtotalStyle));

            $writer->addRow(Row::fromValues([]));

            $granTotalCantidad += $subtotalProductoCantidad;
            $granTotalSubtotal += $subtotalProductoMonto;
        }

        $granTotalRow = [
            '',
            '',
            'TOTAL GENERAL',
            '',
            $granTotalCantidad,
            '',
            $granTotalSubtotal,
        ];
        $writer->addRow(Row::fromValues($granTotalRow, $granTotalStyle));

        $writer->close();

        return $tempFilePath;
    }

    /**
     * Obtiene los años disponibles para reportes multi-periodo
     */
    public static function getYearOptions(?int $sesionId = null): array
    {
        $now = now();
        $startYear = max(2023, $now->year - 3);

        $minVentaDate = Venta::query()
            ->when($sesionId !== null, fn ($q) => $q->where('venta.id_sesion', $sesionId))
            ->min('fecha_venta');
        if ($minVentaDate) {
            $minYear = (int) substr($minVentaDate, 0, 4);
            if ($minYear < $startYear) {
                $startYear = $minYear;
            }
        }

        $options = [];
        for ($year = $now->year; $year >= $startYear; $year--) {
            $options[$year] = (string) $year;
        }

        return $options;
    }

    /**
     * Obtiene las opciones de mes de inicio según tipo de reporte y año.
     * Cada opción muestra el rango completo.
     *
     * @param  string  $tipo  'trimestral' o 'semestral'
     * @param  int  $anio  Año seleccionado
     */
    public static function getMonthStartOptions(string $tipo, int $anio): array
    {
        if (! $tipo || ! $anio) {
            return [];
        }

        $mesesRequeridos = match ($tipo) {
            'trimestral' => 3,
            'semestral' => 6,
            default => 0,
        };

        if ($mesesRequeridos === 0) {
            return [];
        }

        $maxInicio = 12 - $mesesRequeridos + 1;

        $now = now();
        if ($anio == $now->year) {
            $maxInicio = min($maxInicio, $now->month);
        } elseif ($anio > $now->year) {
            return [];
        }

        $options = [];
        for ($m = 1; $m <= $maxInicio; $m++) {
            $mesFin = $m + $mesesRequeridos - 1;
            $options[$m] = self::MESES[$m].' — '.self::MESES[$mesFin];
        }

        return $options;
    }

    /**
     * Genera la etiqueta visual del rango seleccionado.
     */
    public static function getRangeLabel(string $tipo, int $anio, int $mesInicio): string
    {
        if (! $tipo || ! $anio) {
            return '';
        }

        if ($tipo === 'anual') {
            return "Rango: Enero — Diciembre {$anio}";
        }

        if (! $mesInicio) {
            return '';
        }

        $mesesRequeridos = $tipo === 'trimestral' ? 3 : 6;
        $mesFin = $mesInicio + $mesesRequeridos - 1;

        return 'Rango: '.self::MESES[$mesInicio].' — '.self::MESES[$mesFin]." {$anio}";
    }

    /**
     * Determina si el periodo seleccionado incluye meses futuros (datos parciales)
     */
    public static function isParcialPeriod(string $tipo, int $anio, int $mesInicio): bool
    {
        if (! $tipo || ! $anio) {
            return false;
        }

        $now = now();

        if ($anio < $now->year) {
            return false;
        }

        if ($anio > $now->year) {
            return true;
        }

        if ($tipo === 'anual') {
            return $now->month < 12;
        }

        if (! $mesInicio) {
            return false;
        }

        $mesesRequeridos = $tipo === 'trimestral' ? 3 : 6;
        $mesFin = $mesInicio + $mesesRequeridos - 1;

        return $mesFin > $now->month;
    }

    /**
     * Genera un reporte de ventas multi-periodo (trimestral, semestral o anual)
     *
     * @param  string  $tipo  'trimestral', 'semestral' o 'anual'
     * @param  int  $anio  Año del reporte
     * @param  int  $mesInicio  Mes de inicio (1-12). Para anual se ignora y usa 1.
     * @param  int  $sesionId  ID del negocio (sesion) cuyas ventas se reportan
     * @param  array<int>|null  $productIds  IDs opcionales de productos seleccionados
     * @return array Resumen con estado, periodo_label, productos y ruta del archivo
     */
    public function generateMultiPeriodReport(
        string $tipo,
        int $anio,
        int $mesInicio,
        int $sesionId,
        ?array $productIds = null,
    ): array {
        $mesesRequeridos = match ($tipo) {
            'trimestral' => 3,
            'semestral' => 6,
            'anual' => 12,
            default => 3,
        };

        $mesInicioReal = $tipo === 'anual' ? 1 : $mesInicio;
        $mesFin = $mesInicioReal + $mesesRequeridos - 1;

        $startDate = Carbon::create($anio, $mesInicioReal, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($anio, $mesFin, 1)->endOfMonth()->toDateString();

        $now = now();
        $isParcial = false;
        $mesHasta = $mesFin;

        if ($anio == $now->year && $mesFin > $now->month) {
            $isParcial = true;
            $mesHasta = $now->month;
        }

        $mesInicioNombre = self::MESES[$mesInicioReal];
        $mesFinNombre = self::MESES[$mesFin];
        $periodoLabel = "{$mesInicioNombre} — {$mesFinNombre} {$anio}";

        if ($tipo === 'anual') {
            $periodoLabel = "Año {$anio}";
        }

        if ($isParcial) {
            $periodoLabel .= ' (parcial hasta '.self::MESES[$mesHasta].')';
        }

        $query = VentaDetalle::query()
            ->where('venta_detalle.id_sesion', $sesionId)
            ->whereHas('venta', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('fecha_venta', [$startDate, $endDate]);
            })
            ->with(['venta', 'producto']);

        if (! empty($productIds)) {
            $query->whereIn('id_producto', $productIds);
        }

        $ventasDetalle = $query->get();

        if ($ventasDetalle->isEmpty()) {
            $productosSinVentas = ! empty($productIds)
                ? array_values(Producto::query()->where('id_sesion', $sesionId)->whereIn('id', $productIds)->pluck('nombre_producto')->toArray())
                : [];

            return [
                'status' => self::STATUS_NO_SALES,
                'periodo_label' => $periodoLabel,
                'productos_con_ventas' => [],
                'productos_sin_ventas' => $productosSinVentas,
                'file_path' => null,
                'file_name' => null,
            ];
        }

        $productIdsWithSales = $ventasDetalle->pluck('id_producto')->unique()->toArray();
        $productosConVentas = Producto::query()
            ->where('id_sesion', $sesionId)
            ->whereIn('id', $productIdsWithSales)
            ->pluck('nombre_producto', 'id')
            ->toArray();

        $productosSinVentas = [];
        if (! empty($productIds)) {
            $selectedProducts = Producto::query()
                ->where('id_sesion', $sesionId)
                ->whereIn('id', $productIds)
                ->pluck('nombre_producto', 'id')
                ->toArray();

            foreach ($selectedProducts as $id => $nombre) {
                if (! in_array($id, $productIdsWithSales)) {
                    $productosSinVentas[] = $nombre;
                }
            }
        }

        $status = empty($productosSinVentas) ? self::STATUS_ALL_SALES : self::STATUS_PARTIAL_SALES;

        $mesInicioLower = strtolower($mesInicioNombre);
        $mesFinLower = strtolower($mesFinNombre);
        $sufijoParcial = $isParcial ? '_parcial' : '';

        $fileName = match ($tipo) {
            'trimestral' => "reporte_ventas_trimestral_{$mesInicioLower}_{$mesFinLower}_{$anio}{$sufijoParcial}.xlsx",
            'semestral' => "reporte_ventas_semestral_{$mesInicioLower}_{$mesFinLower}_{$anio}{$sufijoParcial}.xlsx",
            'anual' => "reporte_ventas_anual_{$anio}{$sufijoParcial}.xlsx",
            default => "reporte_ventas_{$anio}{$sufijoParcial}.xlsx",
        };

        $filePath = $this->createMultiPeriodExcelFile(
            ventasDetalle: $ventasDetalle,
            tipo: $tipo,
            anio: $anio,
            mesInicio: $mesInicioReal,
            mesFin: $mesFin,
            isParcial: $isParcial,
            mesHasta: $mesHasta,
        );

        return [
            'status' => $status,
            'periodo_label' => $periodoLabel,
            'productos_con_ventas' => $productosConVentas,
            'productos_sin_ventas' => $productosSinVentas,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ];
    }

    /**
     * Crea el archivo XLSX multi-periodo.
     * Para anual: hoja "Resumen Anual" + hoja "Detalle".
     * Para trimestral/semestral: una sola hoja con columna "Mes".
     */
    protected function createMultiPeriodExcelFile(
        Collection $ventasDetalle,
        string $tipo,
        int $anio,
        int $mesInicio,
        int $mesFin,
        bool $isParcial,
        int $mesHasta,
    ): string {
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFilePath = $tempDir.DIRECTORY_SEPARATOR.uniqid('reporte_ventas_multi_', true).'.xlsx';

        $options = new Options;
        $writer = new Writer($options);
        $writer->openToFile($tempFilePath);

        if ($tipo === 'anual') {
            $this->writeAnnualSummarySheet($writer, $ventasDetalle, $anio, $isParcial, $mesHasta);

            $writer->addNewSheetAndMakeItCurrent();
        }

        $this->writeDetailSheet($writer, $ventasDetalle, $isParcial, $mesHasta, $anio, $options);

        $writer->close();

        return $tempFilePath;
    }

    /**
     * Escribe la hoja "Resumen Anual" con totales por producto y mes
     */
    protected function writeAnnualSummarySheet(
        Writer $writer,
        Collection $ventasDetalle,
        int $anio,
        bool $isParcial,
        int $mesHasta,
    ): void {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Resumen Anual');

        $headerStyle = $this->getHeaderStyle();
        $dataStyle = $this->getDataStyle();
        $granTotalStyle = $this->getGranTotalStyle();
        $warningStyle = $this->getWarningStyle();

        if ($isParcial) {
            $mesHastaNombre = self::MESES[$mesHasta];
            $warningText = "REPORTE PARCIAL: Datos disponibles hasta {$mesHastaNombre} {$anio}. Los meses restantes del periodo no contienen datos.";
            $warningRow = array_merge([$warningText], array_fill(0, 13, ''));
            $writer->addRow(Row::fromValues($warningRow, $warningStyle));
            $writer->addRow(Row::fromValues([]));
        }

        $headers = ['Producto'];
        for ($m = 1; $m <= 12; $m++) {
            $label = mb_substr(self::MESES[$m], 0, 3);
            if ($isParcial && $m > $mesHasta) {
                $label .= '*';
            }
            $headers[] = $label;
        }
        $headers[] = 'Total Anual';
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $resumen = [];
        foreach ($ventasDetalle as $detalle) {
            $productoId = $detalle->id_producto;
            $productoNombre = $detalle->producto?->nombre_producto ?? 'Producto';
            $mesVenta = (int) Carbon::parse($detalle->venta?->fecha_venta)->format('n');
            $cantidad = (int) ($detalle->cantidad_vendida_producto ?? 0);
            $precioUnitario = (float) ($detalle->producto?->precio_unidad ?? 0);
            $subtotal = $cantidad * $precioUnitario;

            if (! isset($resumen[$productoId])) {
                $resumen[$productoId] = [
                    'nombre' => $productoNombre,
                    'meses' => array_fill(1, 12, 0.0),
                    'total' => 0.0,
                ];
            }

            $resumen[$productoId]['meses'][$mesVenta] += $subtotal;
            $resumen[$productoId]['total'] += $subtotal;
        }

        uasort($resumen, fn (array $a, array $b): int => strcmp($a['nombre'], $b['nombre']));

        $totalesPorMes = array_fill(1, 12, 0.0);
        $granTotal = 0.0;

        foreach ($resumen as $data) {
            $row = [$data['nombre']];
            for ($m = 1; $m <= 12; $m++) {
                if ($isParcial && $m > $mesHasta) {
                    $row[] = '—';
                } else {
                    $valor = $data['meses'][$m];
                    $row[] = $valor;
                    $totalesPorMes[$m] += $valor;
                }
            }
            $row[] = $data['total'];
            $granTotal += $data['total'];

            $writer->addRow(Row::fromValues($row, $dataStyle));
        }

        $totalRow = ['TOTAL'];
        for ($m = 1; $m <= 12; $m++) {
            if ($isParcial && $m > $mesHasta) {
                $totalRow[] = '—';
            } else {
                $totalRow[] = $totalesPorMes[$m];
            }
        }
        $totalRow[] = $granTotal;
        $writer->addRow(Row::fromValues($totalRow, $granTotalStyle));
    }

    /**
     * Escribe la hoja de detalle agrupada por mes, con productos dentro de cada mes
     */
    protected function writeDetailSheet(
        Writer $writer,
        Collection $ventasDetalle,
        bool $isParcial,
        int $mesHasta,
        int $anio,
        Options $options,
    ): void {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Detalle');

        // Anchos de columna
        $options->setColumnWidth(18, 1);  // Código de Venta
        $options->setColumnWidth(16, 2);  // Fecha de Venta
        $options->setColumnWidth(35, 3);  // Producto
        $options->setColumnWidth(14, 4);  // Lote
        $options->setColumnWidth(18, 5);  // Cantidad Vendida
        $options->setColumnWidth(18, 6);  // Precio Unitario
        $options->setColumnWidth(18, 7);  // Subtotal

        $headerStyle = $this->getHeaderStyle();
        $dataStyle = $this->getDataStyle();
        $subtotalStyle = $this->getSubtotalStyle();
        $monthTotalStyle = $this->getSubtotalProductoStyle();
        $granTotalStyle = $this->getGranTotalStyle();
        $warningStyle = $this->getWarningStyle();
        $monthHeaderStyle = $this->getMonthHeaderStyle();

        if ($isParcial) {
            $mesHastaNombre = self::MESES[$mesHasta];
            $warningText = "REPORTE PARCIAL: Datos disponibles hasta {$mesHastaNombre} {$anio}. Los meses restantes del periodo no contienen datos.";
            $writer->addRow(Row::fromValues([$warningText, '', '', '', '', '', ''], $warningStyle));
            $writer->addRow(Row::fromValues([]));
        }

        $ventasPorMes = $ventasDetalle
            ->sortBy('venta.fecha_venta')
            ->groupBy(function ($detalle) {
                return (int) Carbon::parse($detalle->venta?->fecha_venta)->format('n');
            })
            ->sortKeys();

        $granTotalCantidad = 0;
        $granTotalSubtotal = 0.0;

        foreach ($ventasPorMes as $mesNumero => $detallesDelMes) {
            $mesNombre = self::MESES[$mesNumero] ?? '';

            $writer->addRow(Row::fromValues(
                ["{$mesNombre} {$anio}", '', '', '', '', '', ''],
                $monthHeaderStyle,
            ));

            $headers = [
                'Código de Venta',
                'Fecha de Venta',
                'Producto',
                'Lote',
                'Cantidad Vendida',
                'Precio Unitario',
                'Subtotal',
            ];
            $writer->addRow(Row::fromValues($headers, $headerStyle));

            $totalMesCantidad = 0;
            $totalMesMonto = 0.0;

            $detallesPorProducto = $detallesDelMes
                ->sortBy('producto.nombre_producto')
                ->groupBy('id_producto');

            foreach ($detallesPorProducto as $detallesDelProducto) {
                $nombreProducto = $detallesDelProducto->first()->producto?->nombre_producto ?? 'Producto';
                $subtotalProductoCantidad = 0;
                $subtotalProductoMonto = 0.0;

                foreach ($detallesDelProducto->sortBy('venta.fecha_venta') as $detalle) {
                    $cantidad = (int) ($detalle->cantidad_vendida_producto ?? 0);
                    $precioUnitario = (float) ($detalle->producto?->precio_unidad ?? 0);
                    $subtotal = $cantidad * $precioUnitario;

                    $subtotalProductoCantidad += $cantidad;
                    $subtotalProductoMonto += $subtotal;

                    $writer->addRow(Row::fromValues([
                        $detalle->venta?->codigo_venta ?? '',
                        $detalle->venta?->fecha_venta ?? '',
                        $detalle->producto?->nombre_producto ?? '',
                        $detalle->producto?->lote_producto ?? '',
                        $cantidad,
                        $precioUnitario,
                        $subtotal,
                    ], $dataStyle));
                }

                $writer->addRow(Row::fromValues([
                    '',
                    '',
                    "SUBTOTAL {$nombreProducto}",
                    '',
                    $subtotalProductoCantidad,
                    '',
                    $subtotalProductoMonto,
                ], $subtotalStyle));

                $totalMesCantidad += $subtotalProductoCantidad;
                $totalMesMonto += $subtotalProductoMonto;
            }

            $writer->addRow(Row::fromValues([
                '',
                '',
                "TOTAL {$mesNombre}",
                '',
                $totalMesCantidad,
                '',
                $totalMesMonto,
            ], $monthTotalStyle));

            // Fila vacía de separación entre meses
            $writer->addRow(Row::fromValues([]));

            $granTotalCantidad += $totalMesCantidad;
            $granTotalSubtotal += $totalMesMonto;
        }

        // Total general
        $writer->addRow(Row::fromValues([
            '',
            '',
            'TOTAL GENERAL',
            '',
            $granTotalCantidad,
            '',
            $granTotalSubtotal,
        ], $granTotalStyle));
    }

    protected function getHeaderStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('E2E8F0'));
    }

    protected function getDataStyle(): Style
    {
        return (new Style)
            ->setFontSize(11)
            ->setFontName('Calibri');
    }

    protected function getSubtotalStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('F1F5F9'));
    }

    /**
     * Estilo para el total acumulado por producto (más oscuro que subtotal por mes)
     */
    protected function getSubtotalProductoStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('E2E8F0'));
    }

    protected function getGranTotalStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('CBD5E1'));
    }

    /**
     * Estilo para filas de advertencia (fondo amarillo, negrita, cursiva)
     */
    protected function getWarningStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontItalic()
            ->setFontSize(11)
            ->setFontName('Calibri')
            ->setBackgroundColor(Color::toARGB('FEF3C7'));
    }

    /**
     * Estilo para encabezados de sección mensual (fondo azul oscuro, texto blanco, negrita)
     */
    protected function getMonthHeaderStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontSize(12)
            ->setFontName('Calibri')
            ->setFontColor(Color::toARGB('FFFFFF'))
            ->setBackgroundColor(Color::toARGB('475569'));
    }
}
