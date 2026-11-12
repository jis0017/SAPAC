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
        $lecturasMedidor = [];
        $tarifasMedidor = [];

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
                
                // Obtener lecturas de la tabla "lecturas_medidor" si es servicio medido
                if ($toma->calculo_fijo == 0) {
                    $lecturasMedidor = DB::table('lecturas_medidor')
                        ->where('toma_id', $toma->id_toma)
                        ->orderBy('anio', 'asc')
                        ->orderBy('mes', 'asc')
                        ->get(['anio', 'mes', 'lectura', 'consumo_mes', 'consumo_acumulado', 'es_excedente']);

                    $tarifasMedidor = DB::table('tarifas_config')
                        ->where('id_tipo_predio', $toma->id_tipo_predio)
                        ->orderBy('anio_vigencia', 'desc')
                        ->orderBy('limite_inferior', 'asc')
                        ->get();
                }

                // Respaldo de última lectura
                $ultimaFactura = DB::table('facturas')
                    ->where('toma_id', $toma->id_toma)
                    ->orderBy('id_factura', 'desc')
                    ->first();
                $ultimaLectura = $ultimaFactura ? ($ultimaFactura->lectura_actual ?? ($toma->lectura_base ?? 0)) : ($toma->lectura_base ?? 0);

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

        // 4. Estado de Caja, Corte Activo e Historial de Cortes
        $trabajadorId = Auth::id() ?? 1;
        $corteActivo = CortesCaja::where('id_trabajador', $trabajadorId)
            ->where('estatus', 'ABIERTO')
            ->orderBy('id_corte', 'desc')
            ->first();

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
            'corteActivo', 'historialCortes', 'ultimaLectura',
            'lecturasMedidor', 'tarifasMedidor'
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
            $corteId = null;
            if (Schema::hasTable('cortes_caja')) {
                $corteActivo = DB::table('cortes_caja')
                    ->where('id_trabajador', $trabajadorId)
                    ->where('estatus', 'ABIERTO')
                    ->orderBy('id_corte', 'desc')
                    ->lockForUpdate()
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

                $cuotaBase = 0;
                $saneamiento = 0;
                $infraestructura = 0;
                $descuento = 0;
                $reconexion = 0;
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

                if ($request->monto_condonado > 0 || !empty($request->anios_condonados)) {
                    $detalleAnios = $request->anios_condonados ? "Años condonados: {$request->anios_condonados}. " : "";
                    $motivoDesc = trim($detalleAnios . ($request->motivo_condonacion ?? 'Condonación por ley'));
                }

                // Validar excedente contra tarifas_config si hay consumo medido, o usar monto_excedente de lecturas_medidor
                $consumoM3 = floatval($request->consumo ?? 0);
                $excedente = floatval($request->monto_excedente ?? 0);

                if ($toma->calculo_fijo == 0 && $consumoM3 > 0 && $excedente <= 0) {
                    $rangoTarifa = DB::table('tarifas_config')
                        ->where('id_tipo_predio', $toma->id_tipo_predio)
                        ->where('limite_inferior', '<=', $consumoM3)
                        ->where(function ($q) use ($consumoM3) {
                            $q->where('limite_superior', '>=', $consumoM3)
                              ->orWhereNull('limite_superior');
                        })
                        ->orderBy('anio_vigencia', 'desc')
                        ->first();

                    if ($rangoTarifa && floatval($rangoTarifa->precio_m3_excedente) > 0) {
                        $excedente = round($consumoM3 * floatval($rangoTarifa->precio_m3_excedente), 2);
                    }
                }

                if ($excedente > 0) {
                    $saneamiento += ($excedente * 0.25);
                    $infraestructura += ($excedente * 0.05);
                }

                $cuotaBaseAguaTotal = $cuotaBase + $excedente;
                $subtotalCalc = $cuotaBaseAguaTotal + $saneamiento + $infraestructura + $reconexion + ($request->rezagos ?? 0) + ($request->recargos ?? 0) + ($request->sancion ?? 0);

                // Total calculado de forma segura en el servidor
                $bonificacion = min(floatval($request->monto_condonado ?? 0), max(0, $subtotalCalc - $descuento));
                $totalFacturaSeguro = max(0, round($subtotalCalc - $descuento - $bonificacion, 2));

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
                    'bonificacion'                 => $bonificacion,
                    'subtotal'                     => $subtotalCalc,
                    'iva'                          => 0,
                    'total_factura'                => $totalFacturaSeguro,
                    'periodo_anio'                 => $fechaAct->year,
                    'periodo_mes'                  => $periodoMesTexto,
                    'es_solo_contrato'             => $esSoloContrato,
                    'estatus_pago'                 => 'PAGADA',
                    'motivo_condonacion'           => $motivoDesc,
                    'fecha_condonacion'            => ($bonificacion > 0 || !empty($request->anios_condonados)) ? now() : null,
                    'autorizado_por_trabajador_id' => $trabajadorId,
                    'created_at'                   => now(),
                    'updated_at'                   => now(),
                ]);

                $factura = DB::table('facturas')->where('id_factura', $idFactura)->first();

            } else {
                $facturaExistente = DB::table('facturas')
                    ->where('id_factura', $tipoCobro)
                    ->where('toma_id', $toma->id_toma)
                    ->where('estatus_pago', 'PENDIENTE')
                    ->lockForUpdate()
                    ->first();

                if (!$facturaExistente) {
                    DB::rollBack();
                    return back()->with('error', 'La factura seleccionada no pertenece a esta toma o ya fue pagada.');
                }

                DB::table('facturas')->where('id_factura', $facturaExistente->id_factura)->update([
                    'no_factura'             => $request->no_factura,
                    'estatus_pago'           => 'PAGADA',
                    'fecha_lectura_anterior' => $request->fecha_lec_ant,
                    'fecha_lectura_actual'   => $request->fecha_lec_act,
                    'lectura_anterior'       => $request->lectura_anterior ?? 0,
                    'lectura_actual'         => $request->lectura_actual ?? 0,
                    'consumo'                => $request->consumo ?? 0,
                    'updated_at'             => now()
                ]);
                $factura = DB::table('facturas')->where('id_factura', $facturaExistente->id_factura)->first();
            }

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

            if ($corteId && Schema::hasTable('cortes_caja')) {
                DB::table('cortes_caja')->where('id_corte', $corteId)->increment('total_cobrado_sistema', $factura->total_factura);
            }

            DB::commit();

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

            // Limpiar cualquier espacio o salida previa en el búfer antes de enviar el PDF al navegador
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return $pdf->stream('Recibo_' . $factura->no_factura . '.pdf');

        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Error en PagosController@store: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el cobro: ' . $e->getMessage());
        }
    }
    // GENERAR REPORTE DE CORTE POR RANGO DE FECHAS (SOPORTA MILES DE REGISTROS SIN ERROR 500)
    public function generarReporteCorte(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $request->validate([
            'fecha_inicio'      => 'required|date',
            'fecha_fin'         => 'required|date',
            'tipo_reporte'      => 'required|in:desglosado,concentrado',
            'alcance_cajero'    => 'nullable|string',
            'forma_pago_filtro' => 'nullable|string',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin    = Carbon::parse($request->fecha_fin)->endOfDay();
        $tipoReporte = $request->tipo_reporte;
        $formaFiltro = $request->input('forma_pago_filtro', 'TODAS');
        $alcance     = $request->input('alcance_cajero', $request->input('_alcance_cajero', 'todos'));

        $tablaPagos = Schema::hasTable('pagos_facturas') ? 'pagos_facturas' : 'pagos_historial';

        $baseQuery = DB::table($tablaPagos . ' as p')
            ->join('facturas as f', 'p.factura_id', '=', 'f.id_factura')
            ->whereBetween('p.fecha_pago', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->where('p.estatus_pago', 'VALIDO');

        $nombreOperador = 'TODOS LOS OPERADORES';
        if ($alcance === 'actual' && Auth::check()) {
            $baseQuery->where('p.id_trabajador', Auth::id());
            $nombreOperador = strtoupper(Auth::user()->nombre ?? Auth::user()->usuario_trabajador ?? 'OPERADOR');
        } elseif (Auth::check()) {
            $nombreOperador = strtoupper(Auth::user()->nombre ?? Auth::user()->usuario_trabajador ?? 'ADMINISTRADOR');
        }

        if ($formaFiltro !== 'TODAS') {
            $baseQuery->where('p.forma_pago', $formaFiltro);
        }

        // 1. Totales calculados directamente en MySQL
        $resumenSql = (clone $baseQuery)->selectRaw("
            COUNT(p.id_pago) as cantidad_recibos,
            COALESCE(SUM(f.cuota_base_agua), 0) as agua,
            COALESCE(SUM(f.saneamiento), 0) as saneamiento,
            COALESCE(SUM(f.infraestructura), 0) as infraestructura,
            COALESCE(SUM(f.reconexion), 0) as reconexion,
            COALESCE(SUM(f.rezagos), 0) as rezagos,
            COALESCE(SUM(f.recargos), 0) as recargos,
            COALESCE(SUM(f.sancion), 0) as sancion,
            COALESCE(SUM(COALESCE(f.descuento_social, 0) + COALESCE(f.descuento_pronto_pago, 0) + COALESCE(f.bonificacion, 0)), 0) as descuentos,
            COALESCE(SUM(CASE WHEN p.forma_pago = 'EFECTIVO' THEN p.monto_pagado ELSE 0 END), 0) as efectivo,
            COALESCE(SUM(CASE WHEN p.forma_pago = 'TARJETA' THEN p.monto_pagado ELSE 0 END), 0) as tarjeta,
            COALESCE(SUM(CASE WHEN p.forma_pago = 'TRANSFERENCIA' THEN p.monto_pagado ELSE 0 END), 0) as transferencia,
            COALESCE(SUM(p.monto_pagado), 0) as gran_total
        ")->first();

        $totales = [
            'cantidad_recibos' => (int) ($resumenSql->cantidad_recibos ?? 0),
            'agua'             => (float) ($resumenSql->agua ?? 0),
            'saneamiento'      => (float) ($resumenSql->saneamiento ?? 0),
            'infraestructura'  => (float) ($resumenSql->infraestructura ?? 0),
            'reconexion'       => (float) ($resumenSql->reconexion ?? 0),
            'rezagos'          => (float) ($resumenSql->rezagos ?? 0),
            'recargos'         => (float) ($resumenSql->recargos ?? 0),
            'sancion'          => (float) ($resumenSql->sancion ?? 0),
            'descuentos'       => (float) ($resumenSql->descuentos ?? 0),
            'efectivo'         => (float) ($resumenSql->efectivo ?? 0),
            'tarjeta'          => (float) ($resumenSql->tarjeta ?? 0),
            'transferencia'    => (float) ($resumenSql->transferencia ?? 0),
            'gran_total'       => (float) ($resumenSql->gran_total ?? 0),
        ];

        $registros = collect();
        $concentradoPorDia = collect();

        if ($tipoReporte === 'desglosado') {
            $registros = (clone $baseQuery)
                ->leftJoin('tomas_agua as t', 'f.toma_id', '=', 't.id_toma')
                ->leftJoin('usuarios as u', 't.usuario_id', '=', 'u.id_usuario')
                ->select(
                    'p.fecha_pago',
                    'p.monto_pagado',
                    'p.forma_pago',
                    'f.no_factura',
                    'f.toma_id',
                    'f.periodo_anio',
                    'f.periodo_mes',
                    'f.cuota_base_agua',
                    'f.saneamiento',
                    'f.infraestructura',
                    DB::raw('(COALESCE(f.reconexion,0) + COALESCE(f.rezagos,0) + COALESCE(f.recargos,0) + COALESCE(f.sancion,0)) as otros_cargos'),
                    DB::raw('(COALESCE(f.descuento_social,0) + COALESCE(f.descuento_pronto_pago,0) + COALESCE(f.bonificacion,0)) as descuento_fila'),
                    'u.razon_social',
                    'u.apellidos',
                    't.calle',
                    't.numero_exterior'
                )
                ->orderBy('p.fecha_pago', 'asc')
                ->orderBy('p.id_pago', 'asc')
                ->get();
        } else {
            $concentradoPorDia = (clone $baseQuery)
                ->selectRaw("
                    DATE(p.fecha_pago) as fecha_raw,
                    COUNT(p.id_pago) as recibos,
                    COALESCE(SUM(f.cuota_base_agua), 0) as agua,
                    COALESCE(SUM(f.saneamiento), 0) as saneamiento,
                    COALESCE(SUM(f.infraestructura), 0) as infraestructura,
                    COALESCE(SUM(COALESCE(f.reconexion,0) + COALESCE(f.rezagos,0) + COALESCE(f.recargos,0) + COALESCE(f.sancion,0)), 0) as accesorios,
                    COALESCE(SUM(COALESCE(f.descuento_social,0) + COALESCE(f.descuento_pronto_pago,0) + COALESCE(f.bonificacion,0)), 0) as descuentos,
                    COALESCE(SUM(p.monto_pagado), 0) as total_dia
                ")
                ->groupBy(DB::raw('DATE(p.fecha_pago)'))
                ->orderBy('fecha_raw', 'asc')
                ->get()
                ->map(function ($fila) {
                    return [
                        'fecha'           => Carbon::parse($fila->fecha_raw)->format('d/m/Y'),
                        'recibos'         => $fila->recibos,
                        'agua'            => $fila->agua,
                        'saneamiento'     => $fila->saneamiento,
                        'infraestructura' => $fila->infraestructura,
                        'accesorios'      => $fila->accesorios,
                        'descuentos'      => $fila->descuentos,
                        'total_dia'       => $fila->total_dia,
                    ];
                });
        }

        Carbon::setLocale('es');
        $periodoTexto = 'Periodo del: ' . strtoupper($fechaInicio->translatedFormat('d M Y')) . '  al: ' . strtoupper($fechaFin->translatedFormat('d M Y'));

        // Si son más de 300 recibos en modo Desglosado, usamos el motor PDF nativo del navegador para que cargue en 1 segundo
        $modoImpresionDirecta = ($tipoReporte === 'desglosado' && $totales['cantidad_recibos'] > 300);

        $datosPdf = [
            'registros'            => $registros,
            'totales'              => $totales,
            'concentradoPorDia'    => $concentradoPorDia,
            'tipoReporte'          => $tipoReporte,
            'nombreOperador'       => $nombreOperador,
            'periodoTexto'         => $periodoTexto,
            'formaFiltro'          => $formaFiltro,
            'fechaImpresion'       => now()->format('d/m/Y H:i'),
            'modoImpresionDirecta' => $modoImpresionDirecta,
        ];

        if ($modoImpresionDirecta) {
            return view('pagos.reporte_corte_pdf', $datosPdf);
        }

        $orientacion = ($tipoReporte === 'desglosado') ? 'landscape' : 'portrait';
        $pdf = Pdf::loadView('pagos.reporte_corte_pdf', $datosPdf)->setPaper('letter', $orientacion);
        return $pdf->stream('Reporte_Corte_' . $fechaInicio->format('dmY') . '_' . $fechaFin->format('dmY') . '.pdf');
    }

}