<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TomasAgua;
use App\Models\Calculos;
use App\Models\Facturas;
use App\Models\TarifaConfig;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
class CalculosController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->input('anio', date('Y'));

        $tomas = TomasAgua::with(['usuario', 'calculos' => function ($query) use ($anio) {
            $query->where('anio', $anio);
        }, 'tarifaConfig' => function ($query) use ($anio) {
             $query->where('anio_vigencia', $anio);
        }])
        ->where('calculo_fijo', 0) 
        ->paginate(15); 

        $todasTomasMedidas = TomasAgua::with('usuario')
        ->where('calculo_fijo', 0)
        ->get();

        return view('configuracion.calculos_medidores', compact('tomas', 'anio', 'todasTomasMedidas'));
    }

    public function guardarLectura(Request $request, $toma_id)
    {
        $toma = TomasAgua::findOrFail($toma_id);

        $lecturaAnterior = Calculos::where('toma_id', $toma_id)
                            ->orderBy('anio', 'desc')->orderBy('mes', 'desc')->first();

        $valorAnterior = $lecturaAnterior ? $lecturaAnterior->lectura : $toma->lectura_base;
        $consumo_mes = $request->lectura - $valorAnterior;

        // Evitar que el consumo mensual de negativo por algún error de dedo al capturar
        if ($consumo_mes < 0) $consumo_mes = 0;

        $consumo_acumulado = $lecturaAnterior ? ($lecturaAnterior->consumo_acumulado + $consumo_mes) : $consumo_mes;

        $tarifa = TarifaConfig::where('id_tipo_predio', $toma->id_tipo_predio)->where('anio_vigencia', $request->anio)->first();
        $limite_base = $tarifa ? $tarifa->m3_base : 12;

        Calculos::create([
            'toma_id' => $toma_id,
            'id_trabajador' => Auth::id(),
            'anio' => $request->anio,
            'mes' => $request->mes,
            'lectura' => $request->lectura,
            'consumo_mes' => $consumo_mes,
            'consumo_acumulado' => $consumo_acumulado,
            'es_excedente' => ($consumo_acumulado > $limite_base) ? 1 : 0,
            'fecha_lectura' => now()
        ]);

        return back()->with('success', 'Lectura registrada correctamente.');
    }

    // 1. SOLO GENERAR EL PDF (SIN MODIFICAR LA BASE DE DATOS)
    public function GenerarPdf($toma_id, $anio)
    {
        $toma = TomasAgua::with('usuario')->findOrFail($toma_id);
        $ultimaLectura = Calculos::where('toma_id', $toma_id)->orderBy('anio', 'desc')->orderBy('mes', 'desc')->first();
        
        if (!$ultimaLectura) return back()->with('error', 'No hay lecturas para facturar.');

        $lecturaAnterior = Calculos::where('toma_id', $toma_id)
            ->where('id_lectura', '!=', $ultimaLectura->id_lectura)
            ->orderBy('anio', 'desc')->orderBy('mes', 'desc')->first();
            
        $valorAnterior = $lecturaAnterior ? $lecturaAnterior->lectura : $toma->lectura_base;
        $tarifaBase = TarifaConfig::where('id_tipo_predio', $toma->id_tipo_predio)->where('anio_vigencia', $anio)->first();
        $limite = $tarifaBase ? $tarifaBase->m3_base : 12;

        if ($ultimaLectura->consumo_acumulado <= $limite) {
            return back()->with('error', 'Esta toma no tiene excedentes para mostrar.');
        }

        $excedente_m3 = $ultimaLectura->consumo_acumulado - $limite;
        $rangoTarifa = TarifaConfig::where('id_tipo_predio', $toma->id_tipo_predio)
            ->where('anio_vigencia', $anio)
            ->where('limite_inferior', '<=', $excedente_m3)
            ->where(function($q) use ($excedente_m3) {
                $q->where('limite_superior', '>=', $excedente_m3)
                  ->orWhereNull('limite_superior');
            })->first();

        $precio_m3 = $rangoTarifa ? $rangoTarifa->precio_m3_excedente : 0;
        $costo_agua = $excedente_m3 * $precio_m3;
        $saneamiento = $costo_agua * 0.25;
        $infraestructura = $costo_agua * 0.05;
        $total = $costo_agua + $saneamiento + $infraestructura;

        $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

        $datos = [
            'nombre' => $toma->usuario->razon_social ?? 'USUARIO',
            'lectura_anterior' => $valorAnterior,
            'lectura_actual' => $ultimaLectura->lectura,
            'consumo' => $excedente_m3,
            'cuota_fija' => $costo_agua,
            'saneamiento' => $saneamiento,
            'otros_usos' => $infraestructura,
            'total' => $total,
            'mes_nombre' => $meses[$ultimaLectura->mes - 1],
            'anio' => $anio
        ];

        $pdf = PDF::loadView('configuracion.recibo_excedente', $datos);
        return $pdf->stream('Factura_Excedente_'.$toma_id.'.pdf');
    }

    // 2. CREAR LA DEUDA EN FACTURAS Y REINICIAR METROS (ACCION IRREVERSIBLE)
    public function facturarExcedente(Request $request, $toma_id, $anio)
    {
        $toma = TomasAgua::findOrFail($toma_id);
        $ultimaLectura = Calculos::where('toma_id', $toma_id)->orderBy('anio', 'desc')->orderBy('mes', 'desc')->first();
        
        if (!$ultimaLectura) return back()->with('error', 'No hay lecturas registradas.');

        $tarifaBase = TarifaConfig::where('id_tipo_predio', $toma->id_tipo_predio)->where('anio_vigencia', $anio)->first();
        $limite = $tarifaBase ? $tarifaBase->m3_base : 12;

        if ($ultimaLectura->consumo_acumulado <= $limite) {
            return back()->with('error', 'No hay excedentes para cobrar.');
        }

        $excedente_m3 = $ultimaLectura->consumo_acumulado - $limite;
        $rangoTarifa = TarifaConfig::where('id_tipo_predio', $toma->id_tipo_predio)
            ->where('anio_vigencia', $anio)
            ->where('limite_inferior', '<=', $excedente_m3)
            ->where(function($q) use ($excedente_m3) {
                $q->where('limite_superior', '>=', $excedente_m3)
                  ->orWhereNull('limite_superior');
            })->first();

        $precio_m3 = $rangoTarifa ? $rangoTarifa->precio_m3_excedente : 0;
        $costo_agua = $excedente_m3 * $precio_m3;
        $saneamiento = $costo_agua * 0.25;
        $infraestructura = $costo_agua * 0.05;
        $total = $costo_agua + $saneamiento + $infraestructura;

        // Crear la factura
        Facturas::create([
            'toma_id' => $toma_id,
            'id_tarifa' => $rangoTarifa ? $rangoTarifa->id_tarifa : null,
            'no_factura' => 'EXC-' . $toma_id . '-' . date('YmdHis'),
            'lectura_actual' => $ultimaLectura->lectura,
            'consumo' => $excedente_m3,
            'cuota_base_agua' => $costo_agua,
            'saneamiento' => $saneamiento,
            'infraestructura' => $infraestructura,
            'subtotal' => $costo_agua,
            'total_factura' => $total,
            'periodo_anio' => $anio,
            'periodo_mes' => $ultimaLectura->mes,
            'estatus_pago' => 'PENDIENTE',
            'autorizado_por_trabajador_id' => Auth::id()
        ]);

        // Reiniciar metros acumulados
        $ultimaLectura->update(['consumo_acumulado' => 0]);

        return back()->with('success', 'El cobro del excedente se ha generado correctamente y los metros han vuelto a cero.');
    }
}