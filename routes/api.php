<?php

use App\Http\Controllers\InventarioApiController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\RangoNumeracionController;
use App\Http\Controllers\TributoController;
use App\Http\Controllers\UnidadesMedidaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('rango-numeracion', [RangoNumeracionController::class, 'getRangosNumeracion']);
Route::get('municipios', [MunicipioController::class, 'getMunicipios']);
Route::get('tributos', [TributoController::class, 'getTributos']);
Route::get('unidades-medida', [UnidadesMedidaController::class, 'getUnidadesMedida']);

Route::middleware('n8n.auth')->group(function () {
    Route::prefix('inventario')->group(function () {
        Route::get('/', [InventarioApiController::class, 'index']);
        Route::get('/resumen', [InventarioApiController::class, 'resumen']);
        Route::get('/mas-vendidos', [InventarioApiController::class, 'masVendidos']);
        Route::get('/{id}', [InventarioApiController::class, 'show']);
    });

    Route::prefix('ventas')->group(function () {
        Route::get('/mas-vendidos', [InventarioApiController::class, 'masVendidos']);
    });
});
