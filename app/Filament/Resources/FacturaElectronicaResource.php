<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacturaElectronicaResource\Pages;
use App\Filament\Resources\FacturaElectronicaResource\RelationManagers;
use App\Models\Cliente;
use App\Models\Municipio;
use App\Models\FacturaElectronica;
use App\Models\VentaDetalle;
use App\Models\Venta;
use App\Http\Controllers\FacturaElectronicaController;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use OpenSpout\Reader\CSV\Options;
use function Laravel\Prompts\select;

class FacturaElectronicaResource extends Resource
{
  protected static ?string $model = FacturaElectronica::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-check';

  protected static ?int $navigationSort = 5;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Select::make('forma_pago')
          ->label('Forma de pago')
          ->options([
            '1' => 'pago de contado',
            '2' => 'pago a credito'
          ])
          ->required()
          ->live(),
        DatePicker::make('fecha_vencimiento_factura')
          ->label('Fecha de vencimiendo de la factura')
          ->minDate(now()->addDay())
          ->disabled(fn(Get $get): bool => $get('forma_pago') !== '2'),
        Select::make('codigo_metodo_pago')
          ->label('Metodo de pago')
          ->options([
            '10' => 'Efectivo',
            '42' => 'Consignación',
            '20' => 'Cheque',
            '47' => 'Transferencia',
            '71' => 'Bonos',
            '72' => 'Vales',
            '1' => 'Medio de pago no definido',
            '49' => 'Tarjeta Débito',
            '48' => 'Tarjeta Crédito',
            'ZZZ' => 'Otro*'
          ])
          ->required(),
        Textarea::make('observacion')
          ->label('Observaciones')
          ->maxLength(250)
          ->required(),
        Select::make('id_cliente')
          ->label('Cliente')
          ->relationship(
            name: 'cliente',
            titleAttribute: 'nombres',
            modifyQueryUsing: fn(Builder $query) => $query->where('id_usuario', auth()->id())
          )
          ->getOptionLabelFromRecordUsing(fn($record) => "{$record->nombres} {$record->empresa}")
          ->searchable(['nombres', 'empresa'])
          ->preload()
          ->createOptionForm([
            Hidden::make('id_usuario')
              ->default(auth()->id()),
            Select::make('id_documento_identificacion')
              ->label('Tipo de identificacion')
              ->options([
                '1' => 'Registro civil',
                '2' => 'Tarjeta de identidad',
                '3' => 'Cedula de ciudadania',
                '4' => 'Tarjeta de extranjeria',
                '5' => 'Cedula de extranjeria',
                '6' => 'NIT',
                '7' => 'Pasaporte',
                '8' => 'Documento de identificacion extranjero',
                '9' => 'PEP',
                '10' => 'NIT otro pais',
                '11' => 'NUIP*',
              ])
              ->searchable()
              ->required()
              ->live(),
            TextInput::make('identificacion')
              ->label('Numero de identificacion')
              ->required()
              ->maxLength(12),
            TextInput::make('dv')
              ->label('Digito de verificacion del NIT (dv)')
              ->numeric()
              ->disabled(fn(Get $get): bool => $get('id_documento_identificacion') !== '6'),
            Select::make('tipo_organizacion')
              ->label('Tipo de organizacion')
              ->options([
                '1' => 'Persona juridica',
                '2' => 'Persona natural',
              ])
              ->required()
              ->live(),
            TextInput::make('empresa')
              ->label('Razon social')
              ->maxLength(50)
              ->disabled(fn(Get $get): bool => $get('tipo_organizacion') !== '1'),
            TextInput::make('nombre_comercial')
              ->maxLength(50),
            TextInput::make('nombres')
              ->label('Nombre completo')
              ->maxLength(40)
              ->disabled(fn(Get $get): bool => $get('tipo_organizacion') !== '2'),
            TextInput::make('direccion')
              ->maxLength(40),
            TextInput::make('email')
              ->email()
              ->maxLength(40),
            TextInput::make('n_celular')
              ->label('Numero de celular')
              ->maxLength(13),
            Select::make('id_tributo')
              ->label('Tributo')
              ->options([
                '1' => 'IVA',
                '2' => 'No aplica *'
              ])
              ->required(),
            Select::make('id_municipio')
              ->options(Municipio::query()->pluck('municipality_name', 'id'))
              ->label('Municipio')
              ->searchable()
              ->required(),
          ])
          ->required(),
        Select::make('id_venta')
          ->label('Venta y Productos')
          ->options(function () {
            return Venta::query()
              ->whereHas('ventaDetalle', function ($query) {
                $query->where('id_usuario', auth()->id())
                  ->where('estado_factura_electronica', false);
              })
              ->with(['ventaDetalle.producto'])
              ->get()
              ->mapWithKeys(function ($venta) {
                $listaProductos = $venta->ventaDetalle
                  ->map(function ($detalle) {
                    if ($detalle->producto) {
                      return "{$detalle->producto->nombre_producto} ({$detalle->producto->marca})";
                    }
                    return '';
                  })
                  ->filter()
                  ->join(' - ');
                return [
                  $venta->id => "{$venta->codigo_venta} - {$listaProductos} - Total = {$venta->total_venta}"
                ];
              });
          })
          ->searchable()
          ->required(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('created_at')
          ->label('Fecha Creacion')
          ->date(),
        Tables\Columns\TextColumn::make('codigo_referencia')
          ->searchable(),
        Tables\Columns\TextColumn::make('numero_factura')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('cliente.nombres')
          ->searchable(),
        Tables\Columns\TextColumn::make('fecha_vencimiento_factura')
          ->label('Fecha vencimiento')
          ->date()
          ->sortable(),
      ])
      ->modifyQueryUsing(function (Builder $query) {
        return FacturaElectronica::query()
          ->where('id_usuario', '=', auth()->id())
          ->orderBy('created_at', 'desc');
      })
      ->filters([
        Filter::make('created_at')
          ->form([
            DatePicker::make('fecha_creacion_desde')->maxDate(now()),
            DatePicker::make('fecha_creacion_hasta')->maxDate(now())
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                $data['fecha_creacion_desde'],
                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
              )
              ->when(
                $data['fecha_creacion_hasta'],
                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
              );
          })
      ])
      ->actions([
        Tables\Actions\Action::make('Ver')
          ->label('Ver')
          ->icon('heroicon-o-eye')
          ->tooltip('Ver Factura')
          ->url(fn($record) => route('factura-electronica.show', $record->numero_factura))
          ->openUrlInNewTab(),
        Tables\Actions\Action::make('Descargar')
          ->label('Descargar')
          ->icon('heroicon-o-arrow-down-tray')
          ->tooltip('Descargar factura')
          ->url(fn($record) => route('factura-electronica.descargar', $record->numero_factura)),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListFacturaElectronicas::route('/'),
      'create' => Pages\CreateFacturaElectronica::route('/create'),
    ];
  }
}
