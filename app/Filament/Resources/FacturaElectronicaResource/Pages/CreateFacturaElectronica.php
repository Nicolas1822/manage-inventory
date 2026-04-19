<?php

namespace App\Filament\Resources\FacturaElectronicaResource\Pages;

use App\Filament\Resources\FacturaElectronicaResource;
use App\Http\Controllers\FacturaElectronicaController;
use App\Models\Cliente;
use App\Models\FacturaElectronica;
use App\Models\RangosNumeracion;
use App\Models\VentaDetalle;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class CreateFacturaElectronica extends CreateRecord
{
  protected static string $resource = FacturaElectronicaResource::class;

  protected function getRedirectUrl(): string
  {
    return static::getResource()::getUrl('index');
  }

  protected function handleRecordCreation(array $data): FacturaElectronica
  {
    $rangoNumeracion = RangosNumeracion::query()
      ->select('id')->where('prefix', '=', 'SETP');

    $codigo = rand(1000000, 2000000);
    $codigoReferencia = "fact" . (string) $codigo;

    $dataClientes = Cliente::query()
      ->select(
        'id',
        'id_documento_identificacion',
        'identificacion',
        'dv',
        'empresa',
        'nombre_comercial',
        'nombres',
        'direccion',
        'email',
        'n_celular',
        'tipo_organizacion',
        'id_tributo',
        'id_municipio'
      )
      ->where('id', '=', $data['id_cliente']);

    $obtenerProductos = VentaDetalle::query()
      ->select(
        'producto.lote_producto',
        'producto.nombre_producto',
        'producto.precio_unidad',
        'producto.lote_producto',
        'venta_detalle.cantidad_vendida_producto',
        'producto.porcentaje_descuento',
        'producto.precio_unidad',
        'producto.porcentaje_impuesto',
        'producto.id_unidad_medida',
        'producto.codigo_estandar_id',
        'producto.excluido',
        'producto.id_tributo'
      )
      ->join('producto', 'venta_detalle.id_producto', '=', 'producto.id')
      ->where('venta_detalle.id_venta', '=', $data['id_venta'])
      ->get()->toArray();

    $itemsFormateados = [];

    foreach ($obtenerProductos as $producto) {
      $taxRate = !empty($producto['porcentaje_impuesto'])
        ? number_format(floatval($producto['porcentaje_impuesto']), 2, '.', '')
        : '0.00';

      $item = [
        "code_reference" => (string) $producto['lote_producto'],
        "name" => trim($producto['nombre_producto']),
        "quantity" => floatval($producto['cantidad_vendida_producto']),
        "discount_rate" => floatval($producto['porcentaje_descuento']),
        "price" => floatval($producto['precio_unidad']),
        "tax_rate" => $taxRate,
        "unit_measure_id" => $producto['id_unidad_medida'],
        "standard_code_id" => intval($producto['codigo_estandar_id']),
        "is_excluded" => $producto['excluido'],
        "tribute_id" => $producto['id_tributo'],
        "withholding_taxes" => []
      ];

      // Si el producto tiene retenciones, agregarlas aquí
      // Ejemplo: $item["withholding_taxes"] = $this->obtenerRetenciones($producto['id']);

      $itemsFormateados[] = $item;
    }

    $dataApiFactus = [
      "document" => config('constants.factus.codigo_documento'),
      "numbering_range_id" => $rangoNumeracion->value('id'),
      "reference_code" => $codigoReferencia,
      "observation" => $data['observacion'] ?? '',
      "payment_form" => $data['forma_pago'],
      "payment_due_date" => $data['fecha_vencimiento_factura'] ?? null,
      "payment_method_code" => $data['codigo_metodo_pago'],
      "operation_type" => config('constants.factus.tipo_operacion.Estandar'),
      "customer" => [
        "identification_document_id" => $dataClientes->value('id_documento_identificacion'),
        "identification" => $dataClientes->value('identificacion'),
        "dv" => $dataClientes->value('dv'),
        "company" => $dataClientes->value('empresa'),
        "trade_name" => $dataClientes->value('nombre_comercial'),
        "names" => $dataClientes->value('nombres'),
        "adress" => $dataClientes->value('direccion'),
        "email" => $dataClientes->value('email'),
        "phone" => $dataClientes->value('n_celular'),
        "legal_organization_id" => $dataClientes->value('tipo_organizacion'),
        "tribute_id" => $dataClientes->value('id_tributo'),
        "municipality_id" => $dataClientes->value('id_municipio'),
      ],
      "items" => $itemsFormateados
    ];

    $facturaElectronicaController = resolve(FacturaElectronicaController::class);
    $numeroFactura = $facturaElectronicaController->createFacturaElectronica($dataApiFactus);
    if (!isset($numeroFactura)) {
      Notification::make()
        ->title('Error al momento de generar la factura')
        ->body("Ocurrio un error al generar la facatura electronica")
        ->danger()
        ->send();
      throw new Halt();
    }

    $facturaElectronica = FacturaElectronica::create([
      'id_rango_numeracion' => $dataApiFactus['numbering_range_id'],
      'documento' => $dataApiFactus['document'],
      'codigo_referencia' => $dataApiFactus['reference_code'],
      'observacion' => $dataApiFactus['observation'],
      'forma_pago' => $dataApiFactus['payment_form'],
      'fecha_vencimiento_factura' => $dataApiFactus['payment_due_date'],
      'codigo_metodo_pago' => $dataApiFactus['payment_method_code'],
      'tipo_operacion' => $dataApiFactus['operation_type'],
      'numero_factura' => $numeroFactura,
      'id_cliente' => $dataClientes->value('id'),
      'id_venta' => $data['id_venta'],
      'id_usuario' => auth()->id(),
    ]);

    $facturaElectronica->venta()->where('id', $data['id_venta'])->update(['estado_factura_electronica' => true]);

    return $facturaElectronica;
  }
}
