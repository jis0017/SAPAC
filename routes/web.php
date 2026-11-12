<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TomaAguaController;
use App\Http\Controllers\PozosController;
use App\Http\Controllers\PedidoPipaController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\CuotaPeriodoController;
use App\Http\Controllers\TipoPredioController;
use App\Http\Controllers\TarifaConfigController;
use App\Http\Controllers\CalculosController;
use App\Http\Controllers\TrabajadoresController;

// ---------------------------------------------------------
// RUTAS PÚBLICAS Y AUTENTICACIÓN
// ---------------------------------------------------------
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ---------------------------------------------------------
// RUTAS PRIVADAS (Requieren haber iniciado sesión)
// ---------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    
    // Panel general y Buscador (Acceso para todos los logueados)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/buscador-global', [BuscadorController::class, 'buscar'])->name('buscador.global');
    Route::get('/trabajadores', [TrabajadoresController::class, 'index'])->name('trabajadores.trabajadores');

    // 🟢 MÓDULO ADMINISTRATIVO (Ingenieros y Secretarias)
    Route::middleware(['role:Ingeniero,Secretaria'])->group(function () {
        //Notificaciones
        Route::get('/api/notificaciones-pipas', [PedidoPipaController::class, 'obtenerNotificaciones'])->name('notificaciones.pipas');
        // Gestión de Usuarios
        Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/{id}', [UsuariosController::class, 'show'])->name('usuarios.show');
        Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuariosController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{id}', [UsuariosController::class, 'destroy'])->name('usuarios.destroy');
        Route::get('/usuarios/{id}/tomas', [UsuariosController::class, 'obtenerTomas'])->name('usuarios.tomas');

        // Gestión de Tomas de Agua
        Route::get('/tomas', [TomaAguaController::class, 'index'])->name('tomas.index');
        Route::post('/tomas', [TomaAguaController::class, 'store'])->name('tomas.store');
        Route::put('/tomas/{id}', [TomaAguaController::class, 'update'])->name('tomas.update');       
        Route::delete('/tomas/{id}', [TomaAguaController::class, 'destroy'])->name('tomas.destroy'); 
        Route::get('/tomas/{id_toma}/historial-pagos', [TomaAguaController::class, 'obtenerHistorial'])->name('tomas.historial');
        Route::get('/api/buscar-usuarios', [TomaAguaController::class, 'buscarUsuarios'])->name('api.buscar-usuarios');

        // Gestión de Pagos 
        Route::get('/pagos', [PagosController::class, 'index'])->name('pagos.index');
        Route::post('/pagos', [PagosController::class, 'store'])->name('pagos.store');
        Route::post('/pagos/caja/abrir', [PagosController::class, 'abrirCaja'])->name('pagos.abrirCaja');
        Route::post('/pagos/caja/cerrar', [PagosController::class, 'cerrarCaja'])->name('pagos.cerrarCaja');
        Route::get('/pagos/pdf/{toma_id}/{anio}', [PagosController::class, 'GenerarPdf'])->name('pagos.GenerarPdf');
        Route::get('/pagos/recibo/{toma_id}/{anio}', [PagosController::class, 'GenerarRecibo'])->name('pagos.GenerarRecibo');
        Route::get('/pagos/reporte-corte', [PagosController::class, 'generarReporteCorte'])->name('pagos.reporteCorte');
        
        // Gestión de Pedidos de Pipas
        Route::get('/pedidos-pipa', [PedidoPipaController::class, 'index'])->name('pedidos.pipas');
        Route::get('/api/tomas/verificar-solvencia', [PedidoPipaController::class, 'verificarSolvencia']);
        Route::post('/pedidos-pipa', [PedidoPipaController::class, 'store'])->name('pedidos.pipas.store');
        Route::put('/pedidos-pipa/{id}', [PedidoPipaController::class, 'update'])->name('pedidos.pipas.update');
        Route::delete('/pedidos-pipa/{id}', [PedidoPipaController::class, 'destroy'])->name('pedidos.pipas.destroy');
        

        // Gestión de Lecturas de Medidor
        Route::get('/Calculos_medidor', [CalculosController::class, 'index'])->name('calculos_medidor.index');
        Route::post('/medidores/guardar-lectura/{toma_id}', [CalculosController::class, 'guardarLectura'])->name('calculos_medidor.guardar_lectura');
        Route::get('/medidores/pdf/{toma_id}/{anio}', [CalculosController::class, 'GenerarPdf'])->name('calculos_medidor.GenerarPdf');
        Route::post('/medidores/facturar/{toma_id}/{anio}', [CalculosController::class, 'facturarExcedente'])->name('calculos_medidor.facturar');
        Route::delete('/medidores/eliminar-lectura/{toma_id}', [App\Http\Controllers\CalculosController::class, 'eliminarLectura'])->name('calculos_medidor.eliminar_lectura');
        

        // Gestión de Trabajadores
        Route::get('/trabajadores', [TrabajadoresController::class, 'index'])->name('trabajadores.trabajadores');
        Route::post('/trabajadores', [TrabajadoresController::class, 'store'])->name('trabajadores.store');
        Route::put('/trabajadores/{id}', [TrabajadoresController::class, 'update'])->name('trabajadores.update');
        Route::delete('/trabajadores/{id}', [TrabajadoresController::class, 'destroy'])->name('trabajadores.destroy');
        // Rutas para Tipos de Trabajador / Roles
        Route::post('/trabajadores/tipos', [TrabajadoresController::class, 'storeTipo'])->name('trabajadores.tipos.store');
        Route::put('/trabajadores/tipos/{id}', [TrabajadoresController::class, 'updateTipo'])->name('trabajadores.tipos.update');
        Route::delete('/trabajadores/tipos/{id}', [TrabajadoresController::class, 'destroyTipo'])->name('trabajadores.tipos.destroy');

       
    });

    // 🔵 MÓDULO OPERATIVO EN CAMPO (Ingenieros y Piperos)
    Route::middleware(['role:Ingeniero,Pipero'])->group(function () {
        // Gestión de Pozos
        Route::get('/pozos', [PozosController::class, 'index'])->name('pozos.pozos');
        Route::post('/pozos', [PozosController::class, 'store'])->name('pozos.store');
        Route::put('/pozos/{id}', [PozosController::class, 'update'])->name('pozos.update');
        Route::delete('/pozos/{id}', [PozosController::class, 'destroy'])->name('pozos.destroy');
    });

    // 🔴 MÓDULO DE CONFIGURACIÓN DEL SISTEMA (Solo Ingenieros)
    Route::middleware(['role:Ingeniero'])->group(function () {
        Route::get('/configuracion', [CuotaPeriodoController::class, 'index'])->name('configuracion.tarifas');
        Route::get('/configuracion/tarifas', [CuotaPeriodoController::class, 'index']);
        
        Route::post('/configuracion/cuotas', [CuotaPeriodoController::class, 'store'])->name('configuracion.cuotas.store');
        Route::put('/configuracion/cuotas/{id}', [CuotaPeriodoController::class, 'update'])->name('configuracion.cuotas.update');
        Route::delete('/configuracion/cuotas/{id}', [CuotaPeriodoController::class, 'destroy'])->name('configuracion.cuotas.destroy');
        
        Route::post('/configuracion/tarifas-medidas', [TarifaConfigController::class, 'store'])->name('configuracion.tarifas-medidas.store');
        Route::put('/configuracion/tarifas-medidas/{id}', [TarifaConfigController::class, 'update'])->name('configuracion.tarifas-medidas.update');
        Route::delete('/configuracion/tarifas-medidas/{id}', [TarifaConfigController::class, 'destroy'])->name('configuracion.tarifas-medidas.destroy');
        
        Route::post('/configuracion/tipo-predio', [TipoPredioController::class, 'store'])->name('configuracion.tipo-predio.store');
        Route::put('/configuracion/tipo-predio/{id}', [TipoPredioController::class, 'update'])->name('configuracion.tipo-predio.update');
        Route::delete('/configuracion/tipo-predio/{id}', [TipoPredioController::class, 'destroy'])->name('configuracion.tipo-predio.destroy');
    });
});