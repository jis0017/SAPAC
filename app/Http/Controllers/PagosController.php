<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Facturas;
use App\Models\TomasAgua;
use App\Models\CortesCaja;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PagosController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'toma_id' => 'nullable|integer'
        ]);

        $busqueda = $request->input('q');
        $tomaId = $request->input('toma_id');
        
        $tomasBuscadas = [];
        $toma = null;
        $facturasPendientes = [];
        $alertaContrato = false;
        $cuotaPeriodo = null;
        $adeudosPrevios = 0;
        $ultimaLectura = 0;

        // 1. Buscador seguro
        if ($busqueda) {
            $busquedaLimpia = addcslashes($busqueda, '%_');
            $termino = "%{$busquedaLimpia}%";

            $tomasBuscadas = DB::table('tomas_agua')
                ->leftJoin('usuarios', 'tomas_agua.usuario_id', '=', 'usuarios.id_usuario')
                ->select('tomas_agua.*', 'usuarios.razon_social as nombre_usuario', 'usuarios.apellidos as apellido_usuario')
                ->where(function($query) use ($termino) {
                    $query->where('tomas_agua.id_toma', 'LIKE', $termino)
                          ->orWhere('tomas_agua.no_medidor', 'LIKE', $termino)
                          ->orWhere('tomas_agua.calle', 'LIKE', $termino)
                          ->orWhere('tomas_agua.numero_exterior', 'LIKE', $termino)
                          ->orWhere('tomas_agua.numero_interior', 'LIKE', $termino)
                          ->orWhere('usuarios.razon_social', 'LIKE', $termino)
                          ->orWhere('usuarios.apellidos', 'LIKE', $termino)
                          ->orWhereRaw("CONCAT(tomas_agua.calle, ' ', tomas_agua.numero_exterior) LIKE ?", [$termino]);
                })
                ->get();
                
            if ($tomasBuscadas->count() == 1) {
                return redirect()->route('pagos.index', ['toma_id' => $tomasBuscadas->first()->id_toma]);
            }
        }

        // 2. Cargar datos de la toma seleccionada
        if ($tomaId) {
            $toma = TomasAgua::with('usuario')->find($tomaId);
            
            if ($toma) {
                $cuotaPeriodo = DB::table('cuotas_periodo')
                    ->where('id_tipo_predio', $toma->id_tipo_predio)
                    ->where('id_localidad', $toma->id_localidad)
                    ->orderBy('anio', 'desc')
                    ->first();

                $facturasPendientes = DB::table('facturas')
                    ->where('toma_id', $toma->id_toma)
                    ->where('estatus_pago', 'PENDIENTE')
                    ->orderBy('periodo_anio', 'asc')
                    ->get();
                
                $adeudosPrevios = $facturasPendientes->sum('total_factura');
                
                // Obtener la última lectura registrada si es servicio medido
                $ultimaFactura = DB::table('facturas')
                    ->where('toma_id', $toma->id_toma)
                    ->orderBy('id_factura', 'desc')
                    ->first();
                $ultimaLectura = $ultimaFactura ? ($ultimaFactura->lectura_actual ?? 0) : 0;

                $tieneHistorial = DB::table('facturas')
                    ->where('toma_id', $toma->id_toma)
                    ->whereIn('estatus_pago', ['PAGADA', 'VALIDO'])
                    ->exists();

                if (!$tieneHistorial) {
                    $alertaContrato = true;
                }
            }
        }

        // 3. Calcular siguiente folio sugerido para el lote de facturas
        $ultimoFolio = DB::table('facturas')->orderBy('id_factura', 'desc')->value('no_factura');
        $siguienteNum = 1;
        if ($ultimoFolio && is_numeric($ultimoFolio)) {
            $siguienteNum = intval($ultimoFolio) + 1;
        } elseif ($ultimoFolio && preg_match('/(\d+)$/', $ultimoFolio, $matches)) {
            $siguienteNum = intval($matches[1]) + 1;
        }
        $folioSugerido = str_pad($siguienteNum, 6, '0', STR_PAD_LEFT);

        // 4. Estado de Caja, Corte Activo e Historial de Cortes (cortes_caja)
        $trabajadorId = Auth::id() ?? 1;
        $corteActivo = CortesCaja::where('id_trabajador', $trabajadorId)
            ->where('estatus', 'ABIERTO')
            ->orderBy('id_corte', 'desc')
            ->first();

        // Historial de los últimos 25 cortes de caja para mostrar en la tabla del modal
        $historialCortes = CortesCaja::orderBy('id_corte', 'desc')
            ->limit(25)
            ->get();

        $tablaPagos = Schema::hasTable('pagos_facturas') ? 'pagos_facturas' : 'pagos_historial';
        $queryPagosHoy = DB::table($tablaPagos)
            ->whereDate('fecha_pago', Carbon::today())
            ->where('estatus_pago', 'VALIDO');

        if ($corteActivo && Schema::hasColumn($tablaPagos, 'corte_id')) {
            $queryPagosHoy->where('corte_id', $corteActivo->id_corte);
        }

        $ingresosHoy = (clone $queryPagosHoy)->sum('monto_pagado');
        $pagosHoyCount = (clone $queryPagosHoy)->count();

        return view('pagos.pagos', compact(
            'tomasBuscadas', 'toma', 'cuotaPeriodo', 'busqueda', 
            'facturasPendientes', 'alertaContrato', 'ingresosHoy', 
            'pagosHoyCount', 'adeudosPrevios', 'folioSugerido', 
            'corteActivo', 'historialCortes', 'ultimaLectura'
        ));
    }

    // ABRIR CAJA DEL DÍA
    public function abrirCaja(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:255'
        ]);

        $trabajadorId = Auth::id() ?? 1;

        // Evitar duplicar si ya tiene una caja abierta
        $yaAbierta = CortesCaja::where('id_trabajador', $trabajadorId)
            ->where('estatus', 'ABIERTO')
            ->exists();

        if ($yaAbierta) {
            return back()->with('error', 'Ya tienes un corte de caja abierto actualmente.');
        }

        CortesCaja::create([
            'id_trabajador'            => $trabajadorId,
            'fecha_apertura'           => now(),
            'monto_inicial'            => $request->monto_inicial,
            'total_cobrado_sistema'    => 0.00,
            'total_efectivo_entregado' => 0.00,
            'diferencia'               => 0.00,
            'estatus'                  => 'ABIERTO',
            'observaciones'            => $request->observaciones,
        ]);

        return back()->with('success', 'Caja abierta correctamente.');
    }

    // CERRAR CORTE DE CAJA
    public function cerrarCaja(Request $request)
    {
        $request->validate([
            'id_corte'                 => 'required|integer',
            'total_efectivo_entregado' => 'required|numeric|min:0',
            'observaciones'            => 'nullable|string|max:255'
        ]);

        $corte = CortesCaja::findOrFail($request->id_corte);

        $tablaPagos = Schema::hasTable('pagos_facturas') ? 'pagos_facturas' : 'pagos_historial';
        $queryCobrado = DB::table($tablaPagos)->where('estatus_pago', 'VALIDO');
        
        if (Schema::hasColumn($tablaPagos, 'corte_id')) {
            $queryCobrado->where('corte_id', $corte->id_corte);
        } else {
            $queryCobrado->whereDate('fecha_pago', Carbon::today());
        }

        $totalCobradoSistema = $queryCobrado->sum('monto_pagado');
        $esperadoTotal = $corte->monto_inicial + $totalCobradoSistema;
        $diferencia = $request->total_efectivo_entregado - $esperadoTotal;

        $corte->update([
            'fecha_cierre'             => now(),
            'total_cobrado_sistema'    => $totalCobradoSistema,
            'total_efectivo_entregado' => $request->total_efectivo_entregado,
            'diferencia'               => $diferencia,
            'estatus'                  => 'CERRADO',
            'observaciones'            => $request->observaciones ?? $corte->observaciones,
        ]);

        return back()->with('success', 'Corte de caja cerrado exitosamente.');
    }

    // GUARDAR PAGO E IMPRIMIR PAPELETA
    public function store(Request $request)
    {
        $request->validate([
            'toma_id'            => 'required|exists:tomas_agua,id_toma',
            'no_factura'         => 'required|string|max:50',
            'tipo_cobro'         => 'required', 
            'forma_pago'         => 'required|string',
            'fecha_lec_ant'      => 'required|date',
            'fecha_lec_act'      => 'required|date',
            'lectura_anterior'   => 'nullable|numeric|min:0',
            'lectura_actual'     => 'nullable|numeric|min:0',
            'consumo'            => 'nullable|numeric|min:0',
            'monto_excedente'    => 'nullable|numeric|min:0',
            'rezagos'            => 'nullable|numeric|min:0',
            'recargos'           => 'nullable|numeric|min:0',
            'sancion'            => 'nullable|numeric|min:0',
            'monto_condonado'    => 'nullable|numeric|min:0',
            'anios_condonados'   => 'nullable|string|max:100',
            'motivo_condonacion' => 'nullable|string|max:255',
            'total_factura'      => 'required|numeric|min:0'
        ]);

        $toma = TomasAgua::with('usuario')->findOrFail($request->toma_id);
        $tipoCobro = $request->tipo_cobro; 
        $trabajadorId = Auth::id() ?? 1;

        $cuotaPeriodo = DB::table('cuotas_periodo')
            ->where('id_tipo_predio', $toma->id_tipo_predio)
            ->where('id_localidad', $toma->id_localidad)
            ->orderBy('anio', 'desc')
            ->first();

        if (!$cuotaPeriodo && in_array($tipoCobro, ['periodos', 'contrato', 'reconexion'])) {
            return back()->with('error', 'Error crítico: No existe tarifa configurada para esta toma.');
        }

        DB::beginTransaction();
        try {
            // Verificar o crear corte abierto automáticamente para que nunca falle por corte_id
            $corteId = null;
            if (Schema::hasTable('cortes_caja')) {
                $corteActivo = DB::table('cortes_caja')
                    ->where('id_trabajador', $trabajadorId)
                    ->where('estatus', 'ABIERTO')
                    ->orderBy('id_corte', 'desc')
                    ->first();

                if ($corteActivo) {
                    $corteId = $corteActivo->id_corte;
                } else {
                    $corteId = DB::table('cortes_caja')->insertGetId([
                        'id_trabajador'            => $trabajadorId,
                        'fecha_apertura'           => now(),
                        'monto_inicial'            => 0.00,
                        'total_cobrado_sistema'    => 0.00,
                        'total_efectivo_entregado' => 0.00,
                        'diferencia'               => 0.00,
                        'estatus'                  => 'ABIERTO',
                        'observaciones'            => 'Apertura automática al cobrar',
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]);
                }
            }

            Carbon::setLocale('es');
            $fechaAnt = Carbon::parse($request->fecha_lec_ant);
            $fechaAct = Carbon::parse($request->fecha_lec_act);
            $meses = ($fechaAct->year - $fechaAnt->year) * 12 + ($fechaAct->month - $fechaAnt->month) + 1;
            if ($meses < 1) $meses = 1;

            if ($tipoCobro === 'periodos' || $tipoCobro === 'contrato' || $tipoCobro === 'reconexion') {
                
                $cuotaBase = 0; $saneamiento = 0; $infraestructura = 0; $descuento = 0; $reconexion = 0;
                $motivoDesc = null;
                $esSoloContrato = 0;
                $periodoMesTexto = 'ANUAL';

                if ($tipoCobro === 'contrato') {
                    $cuotaBase = $cuotaPeriodo->costo_nuevo_contrato;
                    $esSoloContrato = 1;
                    $periodoMesTexto = 'CONTRATO';
                } elseif ($tipoCobro === 'reconexion') {
                    $reconexion = $cuotaPeriodo->costo_reconexion;
                    $periodoMesTexto = 'RECONEXIÓN';
                } else { 
                    $cuotaBase = $cuotaPeriodo->cuota_mensual * $meses; 
                    $saneamiento = ($cuotaPeriodo->saneamiento_mensual / 12) * $meses; 
                    $infraestructura = ($cuotaPeriodo->otros_usos_mensual / 12) * $meses; 
                    
                    if ($meses == 12) {
                        $periodoMesTexto = 'ANUAL';
                    } else {
                        $mesIni = strtoupper($fechaAnt->translatedFormat('M'));
                        $mesFin = strtoupper($fechaAct->translatedFormat('M'));
                        $periodoMesTexto = ($mesIni == $mesFin) ? $mesIni : "{$mesIni}-{$mesFin}";
                    }

                    if ($request->tipo_descuento === 'adulto_mayor') {
                        $descuento = $cuotaBase * 0.50; 
                        $motivoDesc = 'INAPAM 50%';
                    } elseif ($request->tipo_descuento === 'enero' && $meses == 12) {
                        $descuento = $cuotaBase * 0.15; 
                        $motivoDesc = 'Enero 15%';
                    } elseif ($request->tipo_descuento === 'marzo_abril' && $meses == 12) {
                        $descuento = $cuotaBase * 0.05;
                        $motivoDesc = 'Mar-Abr 5%';
                    }
                }

                // Armar motivo de condonación incluyendo los años perdonados
                if ($request->monto_condonado > 0 || !empty($request->anios_condonados)) {
                    $detalleAnios = $request->anios_condonados ? "Años condonados: {$request->anios_condonados}. " : "";
                    $motivoDesc = trim($detalleAnios . ($request->motivo_condonacion ?? 'Condonación por ley'));
                }

                $excedente = $request->monto_excedente ?? 0;
                $cuotaBaseAguaTotal = $cuotaBase + $excedente;
                $subtotalCalc = $cuotaBaseAguaTotal + $saneamiento + $infraestructura + $reconexion + ($request->rezagos ?? 0) + ($request->recargos ?? 0) + ($request->sancion ?? 0);

                // Insertar directamente con DB::table para evitar errores de $fillable en el modelo
                $idFactura = DB::table('facturas')->insertGetId([
                    'toma_id'                      => $toma->id_toma,
                    'id_tarifa'                    => $cuotaPeriodo->id_cuota_periodo,
                    'no_factura'                   => $request->no_factura,
                    'fecha_lectura_anterior'       => $request->fecha_lec_ant,
                    'fecha_lectura_actual'         => $request->fecha_lec_act,
                    'lectura_anterior'             => $request->lectura_anterior ?? 0,
                    'lectura_actual'               => $request->lectura_actual ?? 0,
                    'consumo'                      => $request->consumo ?? 0,
                    'cuota_base_agua'              => $cuotaBaseAguaTotal,
                    'saneamiento'                  => $saneamiento,
                    'infraestructura'              => $infraestructura,
                    'costo_medidor'                => 0,
                    'reconexion'                   => $reconexion,
                    'recargos'                     => $request->recargos ?? 0,
                    'rezagos'                      => $request->rezagos ?? 0,
                    'sancion'                      => $request->sancion ?? 0,
                    'descuento_social'             => ($request->tipo_descuento === 'adulto_mayor') ? $descuento : 0,
                    'descuento_pronto_pago'        => in_array($request->tipo_descuento, ['enero', 'marzo_abril']) ? $descuento : 0,
                    'bonificacion'                 => $request->monto_condonado ?? 0,
                    'subtotal'                     => $subtotalCalc,
                    'iva'                          => 0,
                    'total_factura'                => $request->total_factura, 
                    'periodo_anio'                 => $fechaAct->year,
                    'periodo_mes'                  => $periodoMesTexto,
                    'es_solo_contrato'             => $esSoloContrato,
                    'estatus_pago'                 => 'PAGADA',
                    'motivo_condonacion'           => $motivoDesc,
                    'fecha_condonacion'            => ($request->monto_condonado > 0 || !empty($request->anios_condonados)) ? now() : null,
                    'autorizado_por_trabajador_id' => $trabajadorId,
                    'created_at'                   => now(),
                    'updated_at'                   => now(),
                ]);

                $factura = DB::table('facturas')->where('id_factura', $idFactura)->first();

            } else {
                // Pago de factura pendiente existente
                DB::table('facturas')->where('id_factura', $tipoCobro)->update([
                    'no_factura'             => $request->no_factura,
                    'estatus_pago'           => 'PAGADA',
                    'fecha_lectura_anterior' => $request->fecha_lec_ant,
                    'fecha_lectura_actual'   => $request->fecha_lec_act,
                    'lectura_anterior'       => $request->lectura_anterior ?? 0,
                    'lectura_actual'         => $request->lectura_actual ?? 0,
                    'consumo'                => $request->consumo ?? 0,
                    'updated_at'             => now()
                ]);
                $factura = DB::table('facturas')->where('id_factura', $tipoCobro)->first();
            }

            // Registrar en pagos_facturas o pagos_historial (compatible con ambas)
            $tablaPagos = Schema::hasTable('pagos_facturas') ? 'pagos_facturas' : 'pagos_historial';
            $datosPago = [
                'factura_id'    => $factura->id_factura,
                'id_trabajador' => $trabajadorId,
                'fecha_pago'    => now()->toDateString(),
                'monto_pagado'  => $factura->total_factura,
                'forma_pago'    => $request->forma_pago,
                'estatus_pago'  => 'VALIDO',
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if (Schema::hasColumn($tablaPagos, 'corte_id') && $corteId) {
                $datosPago['corte_id'] = $corteId;
            }
            if (Schema::hasColumn($tablaPagos, 'no_transaccion')) {
                $datosPago['no_transaccion'] = $factura->no_factura;
            }

            DB::table($tablaPagos)->insert($datosPago);

            // Actualizar el acumulado en cortes_caja si existe corte abierto
            if ($corteId && Schema::hasTable('cortes_caja')) {
                DB::table('cortes_caja')->where('id_corte', $corteId)->increment('total_cobrado_sistema', $factura->total_factura);
            }

            DB::commit();

            $localidadNombre = 'ACATIC';
            $localidadNombre = 'ACATIC';
            if (Schema::hasTable('localidades') && Schema::hasColumn('localidades', 'nombre_localidad')) {
                $loc = DB::table('localidades')->where('id_localidad', $toma->id_localidad)->value('nombre_localidad');
                if ($loc) $localidadNombre = $loc;
            }
            $vistaPdf = view()->exists('pagos.papeleta_pago') ? 'pagos.papeleta_pago' : 'reportes.papeleta_pago';

            $datosPdf = [
                'factura'         => $factura, 
                'toma'            => $toma, 
                'cuotaPeriodo'    => $cuotaPeriodo,
                'localidadNombre' => $localidadNombre,
                'fecha_pago'      => Carbon::now(),
                'esAnualidad'     => ($factura->periodo_mes === 'ANUAL')
            ];
            
            $pdf = Pdf::loadView($vistaPdf, $datosPdf)->setPaper([0, 0, 612, 396], 'portrait'); 
            return $pdf->stream('Recibo_' . $factura->no_factura . '.pdf');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al generar el cobro: ' . $e->getMessage());
        }
    }
}