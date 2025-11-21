<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Municipio;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthFactusController;

class MunicipioController extends Controller
{
  protected $authFactusController;

  public function __construct(AuthFactusController $authFactusController)
  {
    $this->authFactusController = $authFactusController;
  }

  public function getMunicipios()
  {
    $accessToken = $this->authFactusController->auth();

    if (!$accessToken) {
      return response()->json([
        'error' => 'No se pudo obtener el token',
        'message' => 'Error al momento de realizar la autenticacion',
      ], 400);
    }

    $url = config('constants.URL_API') . '/v1/municipalities';

    $response = Http::withHeaders([
      'Authorization' => 'Bearer ' . $accessToken
    ])->get($url);

    $municipios = json_decode($response->body(), true);
    $message = 'No hay Municipios para actualizar';

    foreach ($municipios['data'] as $municipio) {
      $getMunicipio = Municipio::find($municipio['id']);
      if (!$getMunicipio) {
        Municipio::create([
          'id' => $municipio['id'],
          'code' => $municipio['code'],
          'name' => $municipio['name'],
          'department' => $municipio['department']
        ]);
        $message = 'Tabla actualizada correctamente';
      }
    }
    return response()->json(['message' => $message]);
  }
}
