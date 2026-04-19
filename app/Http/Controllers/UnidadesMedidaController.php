<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthFactusController;
use App\Models\UnidadDeMedida;
use Illuminate\Http\Request;

class UnidadesMedidaController extends Controller
{
  protected $authFactusController;

  public function __construct(AuthFactusController $authFactusController)
  {
    $this->authFactusController = $authFactusController;
  }
  public function getUnidadesMedida()
  {
    $accessToken = $this->authFactusController->auth();

    if (!$accessToken) {
      return response()->json([
        'error' => 'No se pudo obtener el token',
        'message' => 'Error al momento de realizar la autenticacion',
      ], 400);
    }

    $url = config('constants.URL_API') . '/v1/measurement-units?name=';

    $response = Http::withHeaders([
      'Authorization' => 'Bearer ' . $accessToken
    ])->get($url);

    $unidadesMedida = json_decode($response->body(), true);
    $message = 'No hay unidades de medida para actualizar';

    foreach ($unidadesMedida['data'] as $unidad) {
      $getUnidadMedida = UnidadDeMedida::find($unidad['id']);
      if (!$getUnidadMedida) {
        UnidadDeMedida::create([
          'id' => $unidad['id'],
          'code' => $unidad['code'],
          'name' => $unidad['name'],
        ]);
        $message = 'Tabla actualizada correctamente';
      }
    }

    return response()->json(['message' => $message]);
  }
}
