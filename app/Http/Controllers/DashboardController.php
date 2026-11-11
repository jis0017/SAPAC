<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TomasAgua;
use App\Models\PedidoPipa;
use App\Models\Pozos;
use App\Models\Pagos; // Asumiendo que tienes este modelo
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Tarjetas Superiores (KPIs)
        $totalTomas = TomasAgua::count();
        $pipasPendientes = PedidoPipa::where('estado_entrega', 'Pendiente')->count(); 
        $pozosActivos = Pozos::where('estado', 'Activo')->count(); 


        // 2. Datos para Gráfico: Tipos de Tomas
        $tomasFijas = TomasAgua::where('calculo_fijo', 1)->count();
        $tomasMedidas = TomasAgua::where('calculo_fijo', 0)->count();

        // 3. Datos para Gráfico: Estado de Pipas del Mes Actual
        $mesActual = Carbon::now()->month;
        
        // CORREGIDO: Se usa 'estado_entrega' según tu base de datos
        $pipasEntregadas = PedidoPipa::where('estado_entrega', 'Entregado')
                                     ->whereMonth('created_at', $mesActual)
                                     ->count();
                                     
        $pipasCanceladas = PedidoPipa::where('estado_entrega', 'Cancelado')
                                     ->whereMonth('created_at', $mesActual)
                                     ->count();

        // 4. Datos para Gráfico: Ingresos Mensuales (Ejemplo de consulta comentada para usar más adelante)
        /* 
        $ingresosMensuales = Pagos::selectRaw('MONTH(fecha_pago) as mes, SUM(monto) as total')
            ->whereYear('fecha_pago', date('Y'))
            ->groupBy('mes')
            ->pluck('total', 'mes')->toArray();
        */

        return view('index', compact(
            'totalTomas', 
            'pipasPendientes', 
            'pozosActivos',
            'tomasFijas',
            'tomasMedidas',
            'pipasEntregadas',
            'pipasCanceladas'
        ));
    }
}