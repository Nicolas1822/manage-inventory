<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacturaElectronicaController extends Controller
{
    protected $authFactusController;

    private $accessToken;

    public function __construct(AuthFactusController $authFactusController)
    {
        $this->authFactusController = $authFactusController;
    }

    public function storeFacturaElectronica($data)
    {
        $url = config('constants.URL_API_v1_FACTURA_ELECTRONICA').'/validate';

        return $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
        ])->post($url, $data);
    }

    public function createFacturaElectronica($dataFactura)
    {
        try {
            $this->accessToken = $this->authFactusController->auth();
            $response = $this->storeFacturaElectronica($dataFactura);
            if ($response->status() !== 201) {
                Log::error('Error en API Factus al crear factura electrónica', [
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body(),
                    'data' => $dataFactura,
                ]);

                return null;
            }

            return $response->json()['data']['bill']['number'];
        } catch (\Throwable $e) {
            Log::error('Excepción al crear la factura electrónica en createFacturaElectronica: '.$e->getMessage(), [
                'exception' => $e,
                'data' => $dataFactura,
            ]);
            throw $e;
        }
    }

    private function getPdfFactura(string $numeroFactura)
    {
        $url = config('constants.URL_API_v1_FACTURA_ELECTRONICA').'/download-pdf/'.$numeroFactura;
        $this->accessToken = $this->authFactusController->auth();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->accessToken,
        ])->get($url);

        if ($response->status() !== 200) {
            Log::error('Error al obtener factura', [
                'numero' => $numeroFactura,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();
        $pdfBase64 = $data['data']['pdf_base_64_encoded'] ?? null;
        if (! $pdfBase64) {
            Log::error('La respuesta no contiene PDF base64', ['numero' => $numeroFactura]);

            return null;
        }

        return [
            'content' => base64_decode($pdfBase64),
            'filename' => $data['data']['file_name'] ?? "factura-{$numeroFactura}",
        ];
    }

    public function downloadFactura($numeroFactura)
    {
        $pdfData = $this->getPdfFactura($numeroFactura);
        if (! $pdfData) {
            abort(404, 'No se pudo obtener la factura para descargar');
        }

        $pdfContent = $pdfData['content'];

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, "{$pdfData['filename']}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function showFactura(string $numeroFactura)
    {
        $pdfData = $this->getPdfFactura($numeroFactura);
        if (! $pdfData) {
            abort(404, 'No se pudo obtener la factura para visualizar');
        }

        return response($pdfData['content'], 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$pdfData['filename'].'.pdf"');
    }
}
