<?php

namespace App\Filament\Pages;

use App\Services\ReporteVentasService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            $this->getGenerarReporteVentasAction(),
        ];
    }

    protected function getGenerarReporteVentasAction(): Action
    {
        return Action::make('generar_reporte_ventas')
            ->label('Generar Reporte de Ventas')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->modalHeading('Generar Reporte de Ventas')
            ->modalDescription('Seleccione el tipo de reporte y el periodo para generar el reporte de productos vendidos en Excel.')
            ->modalSubmitActionLabel('Generar')
            ->modalCancelActionLabel('Cancelar')
            ->form([
                Select::make('tipo_reporte')
                    ->label('Tipo de Reporte')
                    ->options([
                        'mensual' => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ])
                    ->default('mensual')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('periodo', null);
                        $set('anio', null);
                        $set('mes_inicio', null);
                    }),

                Select::make('periodo')
                    ->label('Mes y Año')
                    ->options(ReporteVentasService::getPeriodOptions())
                    ->default(now()->format('Y-m'))
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get): bool => $get('tipo_reporte') === 'mensual'),

                Select::make('anio')
                    ->label('Año')
                    ->options(fn (): array => ReporteVentasService::getYearOptions())
                    ->default(now()->year)
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('mes_inicio', null);
                    })
                    ->visible(fn (Get $get): bool => in_array($get('tipo_reporte'), ['trimestral', 'semestral', 'anual'])),

                Select::make('mes_inicio')
                    ->label('Mes de Inicio')
                    ->options(fn (Get $get): array => ReporteVentasService::getMonthStartOptions(
                        $get('tipo_reporte') ?? '',
                        (int) ($get('anio') ?? 0)
                    ))
                    ->searchable()
                    ->required()
                    ->live()
                    ->visible(fn (Get $get): bool => in_array($get('tipo_reporte'), ['trimestral', 'semestral'])),

                Placeholder::make('rango_info')
                    ->label('')
                    ->content(fn (Get $get): string => ReporteVentasService::getRangeLabel(
                        $get('tipo_reporte') ?? '',
                        (int) ($get('anio') ?? 0),
                        (int) ($get('mes_inicio') ?? 0)
                    ))
                    ->visible(
                        fn (Get $get): bool => $get('tipo_reporte') !== 'mensual'
                        && $get('anio')
                        && ($get('tipo_reporte') === 'anual' || $get('mes_inicio'))
                    ),

                Placeholder::make('advertencia_parcial')
                    ->label('')
                    ->content(fn (): HtmlString => new HtmlString(
                        '<div style="color: #92400e; background: #fef3c7; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem;">'
                        .'El periodo seleccionado incluye meses futuros. El reporte contendrá datos parciales hasta el mes actual.'
                        .'</div>'
                    ))
                    ->visible(fn (Get $get): bool => ReporteVentasService::isParcialPeriod(
                        $get('tipo_reporte') ?? '',
                        (int) ($get('anio') ?? 0),
                        (int) ($get('mes_inicio') ?? 0)
                    )),
            ])
            ->action(function (array $data, ReporteVentasService $service): ?BinaryFileResponse {
                $tipo = $data['tipo_reporte'] ?? 'mensual';

                if ($tipo === 'mensual') {
                    $result = $service->generateReport(
                        period: $data['periodo'],
                        userId: (int) auth()->id()
                    );
                } else {
                    $result = $service->generateMultiPeriodReport(
                        tipo: $tipo,
                        anio: (int) $data['anio'],
                        mesInicio: (int) ($data['mes_inicio'] ?? 1),
                        userId: (int) auth()->id()
                    );
                }

                if ($result['status'] === ReporteVentasService::STATUS_NO_SALES) {
                    Notification::make()
                        ->warning()
                        ->title("No se registraron ventas en {$result['periodo_label']}.")
                        ->send();

                    return null;
                }

                if ($result['status'] === ReporteVentasService::STATUS_PARTIAL_SALES && ! empty($result['productos_sin_ventas'])) {
                    $productosLista = implode(', ', $result['productos_sin_ventas']);
                    Notification::make()
                        ->info()
                        ->title('Productos sin ventas')
                        ->body("Los siguientes productos no registraron ventas en {$result['periodo_label']}: {$productosLista}")
                        ->send();
                }

                return response()->download($result['file_path'], $result['file_name'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            });
    }
}
