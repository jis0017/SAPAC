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

        $tomas = TomasAgua::with([
            'usuario',
            'calculos' => function ($query) use ($anio) {
                $query->where('anio', $anio)->orderBy('mes', 'asc');
            },
            'tarifaConfig' => function ($query) use ($anio) {
                $query->where('anio_vigencia', $anio);
            }
        ])
        ->where('calculo_fijo', 0)
        ->paginate(15)
        ->appends(['anio' => $anio]);

        $todasTomasMedidas = TomasAgua::with([
            'usuario',
            'calculos' => function ($query) use ($anio) {
                $query->where('anio', $anio)->orderBy('mes', 'asc');
            }
        ])
        ->where('calculo_fijo', 0)
        ->get();

        return view('configuracion.calculos_medidores', compact('tomas', 'anio', 'todasTomasMedidas'));
    }

    public function guardarLectura(Request $request, $toma_id)
    {
        $request->validate([
            'anio'    => 'required|integer',
            'mes'     => 'required|integer|min:1|max:12',
            'lectura' => 'required|numeric|min:0',
        ]);

        $toma = TomasAgua::findOrFail($toma_id);

        // Detectar si el mes ya existe para MODIFICAR o si está vacío para AÑADIR
        $registroExistente = Calculos::where('toma_id', $toma_id)
            ->where('anio', $request->anio)
            ->where('mes', $request->mes)
            ->first();

        $esModificacion = $registroExistente ? true : false;

        Calculos::updateOrCreate(
            [
                'toma_id' => $toma_id,
                'anio'    => $request->anio,
                'mes'     => $request->mes,
            ],
            [
                'id_trabajador' => Auth::id() ?? 1,
                'lectura'       => $request->lectura,
                'fecha_lectura' => now(),
            ]
        );

        // Recalcular cronológicamente todos los meses del año para esa toma
        $this->recalcularAnioToma($toma, $request->anio);

        $nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $nombreMes = $nombresMeses[$request->mes - 1] ?? '';

        $mensaje = $esModificacion
            ? "La lectura de {$nombreMes} fue modificada y los consumos se recalcularon correctamente."
            : "La lectura de {$nombreMes} fue añadida correctamente.";

        return back()->with('success', $mensaje);
    }

    /**
     * Regla de Negocio Tequililla:
     * - Franquicia anual: 144 m³ (12 m³ por mes).
     * - Si no se ha excedido en el año: se resta la lectura base del año pasado (L. ANT) a la lectura actual.
     * - Si ya se excedió en un mes previo: se resta la lectura del mes anterior a la del mes actual.
     */
    private function recalcularAnioToma(TomasAgua $toma, $anio)
    {
        $lecturas = Calculos::where('toma_id', $toma->id_toma)
            ->where('anio', $anio)
            ->orderBy('mes', 'asc')
            ->get();

        if ($lecturas->isEmpty()) return;

        // Obtener lectura base (del año inmediato anterior o el campo lectura_base)
        $lecturaAnioPasado = Calculos::where('toma_id', $toma->id_toma)
            ->where('anio', '<', $anio)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->value('lectura');

        $lecturaBaseAnual = $lecturaAnioPasado ?? ($toma->lectura_base ?? 0);

        // Límite de franquicia anual: 144 m³ para Tequililla (o lo que indique la toma)
        $limiteAnual = ($toma->aplica_franquicia_anual && $toma->volumen_franquicia_m3 > 0)
            ? $toma->volumen_franquicia_m3
            : 144;

        $lecturaMesAnterior = $lecturaBaseAnual;
        $yaSeExcedioAntes = false;

        foreach ($lecturas as $registro) {
            // Consumo específico de este mes respecto al mes anterior
            $consumoDeEsteMes = $registro->lectura - $lecturaMesAnterior;
            if ($consumoDeEsteMes < 0) $consumoDeEsteMes = 0;

            // Gasto total acumulado desde la lectura del año pasado
            $gastoDesdeAnioPasado = $registro->lectura - $lecturaBaseAnual;
            if ($gastoDesdeAnioPasado < 0) $gastoDesdeAnioPasado = 0;

            if (!$yaSeExcedioAntes) {
                if ($gastoDesdeAnioPasado > $limiteAnual) {
                    // Primer mes en que rebasa los 144 m³ anuales
                    $esExcedente = 1;
                    $yaSeExcedioAntes = true;
                } else {
                    $esExcedente = 0;
                }
            } else {
                // Como ya rebasó la franquicia en un mes anterior, cada nuevo m³ consumido en el mes es excedente
                $esExcedente = ($consumoDeEsteMes > 0) ? 1 : 0;
            }

            $registro->update([
                'consumo_mes'       => $consumoDeEsteMes,
                'consumo_acumulado' => $gastoDesdeAnioPasado,
                'es_excedente'      => $esExcedente,
            ]);

            $lecturaMesAnterior = $registro->lectura;
        }
    }

    /**
     * Calcula los metros excedentes a cobrar y su desglose según Ley de Ingresos (Art. 58 y 59 BIS)
     */
    private function calcularDatosExcedente(TomasAgua $toma, $anio)
    {
        $lecturas = Calculos::where('toma_id', $toma->id_toma)
            ->where('anio', $anio)
            ->orderBy('mes', 'asc')
            ->get();

        if ($lecturas->isEmpty()) return null;

        $lecturaAnioPasado = Calculos::where('toma_id', $toma->id_toma)
            ->where('anio', '<', $anio)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->value('lectura');

        $lecturaBaseAnual = $lecturaAnioPasado ?? ($toma->lectura_base ?? 0);
        $limiteAnual = ($toma->aplica_franquicia_anual && $toma->volumen_franquicia_m3 > 0)
            ? $toma->volumen_franquicia_m3
            : 144;

        $ultimaLectura = $lecturas->last();
        $penultimaLectura = $lecturas->count() > 1 ? $lecturas->slice(-2, 1)->first() : null;

        // Verificar si antes de este último mes ya se había rebasado el límite anual
        $acumuladoPrevio = $penultimaLectura ? $penultimaLectura->consumo_acumulado : 0;

        if ($ultimaLectura->consumo_acumulado <= $limiteAnual) {
            $excedente_m3 = 0;
            $valorAnteriorRecibo = $lecturaBaseAnual;
        } elseif ($acumuladoPrevio <= $limiteAnual) {
            // Es el primer mes que se excede: se cobra (Lectura Actual - Lectura Año Pasado) - 144 m³
            $excedente_m3 = $ultimaLectura->consumo_acumulado - $limiteAnual;
            $valorAnteriorRecibo = $lecturaBaseAnual + $limiteAnual;
        } else {
            // Ya se había excedido en un mes anterior: se resta el mes donde se excedió con el mes actual
            $excedente_m3 = $ultimaLectura->consumo_mes;
            $valorAnteriorRecibo = $penultimaLectura->lectura;
        }

        // Buscar tarifa en BD o usar tabulador oficial de Ley de Ingresos 2026 (Art. 58)
        $rangoTarifa = TarifaConfig::where('id_tipo_predio', $toma->id_tipo_predio)
            ->where('anio_vigencia', $anio)
            ->where('limite_inferior', '<=', $excedente_m3)
            ->where(function($q) use ($excedente_m3) {
                $q->where('limite_superior', '>=', $excedente_m3)
                  ->orWhereNull('limite_superior');
            })->first();

        if ($rangoTarifa && $rangoTarifa->precio_m3_excedente > 0) {
            $precio_m3 = $rangoTarifa->precio_m3_excedente;
        } else {
            // Respaldo Oficial Ley de Ingresos Acatic 2026 - Art. 58 (Uso Doméstico)
            $m3ParaRango = $excedente_m3 + 12; 
            if ($m3ParaRango <= 20) $precio_m3 = 8.34;
            elseif ($m3ParaRango <= 30) $precio_m3 = 10.21;
            elseif ($m3ParaRango <= 45) $precio_m3 = 11.33;
            elseif ($m3ParaRango <= 60) $precio_m3 = 13.69;
            elseif ($m3ParaRango <= 75) $precio_m3 = 17.80;
            elseif ($m3ParaRango <= 90) $precio_m3 = 20.54;
            else $precio_m3 = 25.63;
        }

        $costo_agua = $excedente_m3 * $precio_m3;
        $saneamiento = $costo_agua * 0.25;     // Art. 59 BIS Fracción X (25%)
        $infraestructura = $costo_agua * 0.05; // Art. 59 BIS Fracción XI (5%)
        $total = $costo_agua + $saneamiento + $infraestructura;

        return [
            'ultima_lectura'   => $ultimaLectura,
            'lectura_anterior' => $valorAnteriorRecibo,
            'lectura_actual'   => $ultimaLectura->lectura,
            'excedente_m3'     => $excedente_m3,
            'precio_m3'        => $precio_m3,
            'costo_agua'       => $costo_agua,
            'saneamiento'      => $saneamiento,
            'infraestructura'  => $infraestructura,
            'total'            => $total,
            'id_tarifa'        => $rangoTarifa ? $rangoTarifa->id_tarifa : null,
        ];
    }

    // 1. SOLO GENERAR EL PDF (SIN MODIFICAR LA BASE DE DATOS)
    public function GenerarPdf($toma_id, $anio)
    {
        $toma = TomasAgua::with('usuario')->findOrFail($toma_id);
        $calculo = $this->calcularDatosExcedente($toma, $anio);

        if (!$calculo || $calculo['excedente_m3'] <= 0) {
            return back()->with('error', 'Esta toma no tiene metros cúbicos excedentes para facturar.');
        }

        $meses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

        $datos = [
            'nombre'           => $toma->usuario->razon_social ?? 'USUARIO',
            'lectura_anterior' => $calculo['lectura_anterior'],
            'lectura_actual'   => $calculo['lectura_actual'],
            'consumo'          => $calculo['excedente_m3'],
            'cuota_fija'       => $calculo['costo_agua'],
            'saneamiento'      => $calculo['saneamiento'],
            'otros_usos'       => $calculo['infraestructura'],
            'total'            => $calculo['total'],
            'mes_nombre'       => $meses[$calculo['ultima_lectura']->mes - 1],
            'anio'             => $anio
        ];

        $pdf = Pdf::loadView('configuracion.recibo_excedente', $datos);
        return $pdf->stream('Aviso_Excedente_Toma_' . $toma_id . '.pdf');
    }

    // 2. CREAR LA DEUDA EN FACTURAS Y REINICIAR METROS
    public function facturarExcedente(Request $request, $toma_id, $anio)
    {
        $toma = TomasAgua::findOrFail($toma_id);
        $calculo = $this->calcularDatosExcedente($toma, $anio);

        if (!$calculo || $calculo['excedente_m3'] <= 0) {
            return back()->with('error', 'No hay excedentes para enviar a cobro.');
        }

        $ultimaLectura = $calculo['ultima_lectura'];

        Facturas::create([
            'toma_id'                      => $toma_id,
            'id_tarifa'                    => $calculo['id_tarifa'],
            'no_factura'                   => 'EXC-' . $toma_id . '-' . date('YmdHis'),
            'fecha_lectura_anterior'       => now()->startOfMonth()->toDateString(),
            'fecha_lectura_actual'         => now()->toDateString(),
            'lectura_anterior'             => $calculo['lectura_anterior'],
            'lectura_actual'               => $calculo['lectura_actual'],
            'consumo'                      => $calculo['excedente_m3'],
            'cuota_base_agua'              => $calculo['costo_agua'],
            'saneamiento'                  => $calculo['saneamiento'],
            'infraestructura'              => $calculo['infraestructura'],
            'subtotal'                     => $calculo['costo_agua'],
            'total_factura'                => $calculo['total'],
            'periodo_anio'                 => $anio,
            'periodo_mes'                  => 'EXCEDENTE MES ' . $ultimaLectura->mes,
            'estatus_pago'                 => 'PENDIENTE',
            'autorizado_por_trabajador_id' => Auth::id() ?? 1
        ]);

        // Actualizamos la lectura base de la toma para que el próximo mes parta desde aquí
        $toma->update(['lectura_base' => $ultimaLectura->lectura]);
        $ultimaLectura->update(['consumo_acumulado' => 0, 'es_excedente' => 0]);

        return back()->with('success', 'El cobro del excedente se envió a Caja y el contador de excedentes quedó al día.');
    }

    public function eliminarLectura(Request $request, $toma_id)
    {
        $request->validate([
            'anio' => 'required|integer',
            'mes'  => 'required|integer|min:1|max:12',
        ]);

        $toma = TomasAgua::findOrFail($toma_id);

        $eliminado = Calculos::where('toma_id', $toma_id)
            ->where('anio', $request->anio)
            ->where('mes', $request->mes)
            ->delete();

        if (!$eliminado) {
            return back()->with('error', 'No se encontró ninguna lectura en ese mes para eliminar.');
        }

        // Recalcular en cascada los meses restantes del año
        $this->recalcularAnioToma($toma, $request->anio);

        $nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $nombreMes = $nombresMeses[$request->mes - 1] ?? '';

        return back()->with('success', "La lectura de {$nombreMes} fue eliminada y los consumos del año se recalcularon.");
    }
}