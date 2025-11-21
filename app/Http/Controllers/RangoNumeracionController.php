<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RangosNumeracion;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthFactusController;

class RangoNumeracionController extends Controller
{
  protected $authFactusController;

  public function __construct(AuthFactusController $authFactusController)
  {
    $this->authFactusController = $authFactusController;
  }

  public function getRangosNumeracion()
  {
    $accessToken = $this->authFactusController->auth();

    if (!$accessToken) {
      return response()->json([
        'error' => 'No se pudo obtener el token',
        'message' => 'Error al momento de realizar la autenticacion',
      ], 400);
    }

    $url = config('constants.URL_API') . '/v1/numbering-ranges';
    $params = [
      'filter[id]' => null,
      'filter[document]' => null,
      'filter[resolution_number]' => null,
      'filter[technical_key]' => null,
      'filter[is_active]' => null
    ];

    $response = Http::withHeaders([
      'Authorization' => 'Bearer ' . $accessToken,
    ])->get($url, $params);

    $rangosNumeracion = json_decode($response->body(), true);
    $message = 'No hay rangos para actualizar';

    foreach ($rangosNumeracion['data'] as $rango) {
      foreach ($rango as $r) {
        $getRangosNumeracion = RangosNumeracion::find($r['id']);
        if (!$getRangosNumeracion) {
          RangosNumeracion::create([
            'id' => $r['id'],
            'document' => $r['document'],
            'prefix' => $r['prefix'],
            'from' => $r['from'],
            'to' => $r['to'],
            'current' => $r['current'],
            'resolution_number' => $r['resolution_number'],
            'start_date' => $r['start_date'],
            'end_date' => $r['end_date'],
            'technical_key' => $r['technical_key'],
            'is_expired' => $r['is_expired'],
            'is_active' => $r['is_active'],
            'created_at' => $r['created_at'],
            'updated_at' => $r['updated_at'],
          ]);
          $message = 'Tabla actualizada correctamente';
        }
      }
      return response()->json(['message' => $message]);
    }
  }
}
