<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthFactusController;
use App\Models\Tributo;

class TributoController extends Controller
{
  protected $authFactusController;

  public function __construct(AuthFactusController $authFactusController)
  {
    $this->authFactusController = $authFactusController;
  }

  public function getTributos()
  {
    $accessToken = $this->authFactusController->auth();

    if (!$accessToken) {
      return response()->json([
        'error' => 'No se pudo obtener el token',
        'message' => 'Error al momento de realizar la autenticacion',
      ], 400);
    }

    $url = config('constants.URL_API') . '/v1/tributes/products?name=';

    $response = Http::withHeaders([
      'Authorization' => 'Bearer ' . $accessToken
    ])->get($url);

    $tributos = json_decode($response->body(), true);
    $message = 'No hay tributos para actualizar';

    foreach ($tributos['data'] as $tributo) {
      $getTributo = Tributo::find($tributo['id']);
      if (!$getTributo) {
        Tributo::create([
          'id' => $tributo['id'],
          'code' => $tributo['code'],
          'name' => $tributo['name'],
          'description' => $tributo['description']
        ]);
        $message = 'Tabla actualizada correctamente';
      }
    }
    return response()->json(['message' => $message]);
  }
}
