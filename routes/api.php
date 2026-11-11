<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PedidoPipaController;

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

// login para el aplicacion móvil
Route::post('/mobile/login', [LoginController::class, 'apiLogin']);
// Rutas para el pipero
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/mobile/logout', [LoginController::class, 'apiLogout']);
    Route::get('/mobile/pedidos', [PedidoPipaController::class, 'misPedidosMobile']);
    Route::put('/mobile/pedidos/{id}/estado', [PedidoPipaController::class, 'actualizarEstadoMobile']);
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

