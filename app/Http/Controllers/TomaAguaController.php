<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TomasAgua; 
use App\Models\usuarios;  
use App\Models\Localidades; 
use App\Models\TipoPredio; 
use Illuminate\Support\Facades\DB;

class TomaAguaController extends Controller
{
    public function index(Request $request)
    {
        // 1. Iniciar la consulta base con sus relaciones
        $query = TomasAgua::with(['usuario', 'tipoPredio', 'localidad']);

        // 2. Búsqueda exacta desde el buscador global (clic directo en el resultado)
        if ($request->has('id_exacto') && $request->id_exacto != '') {
            $query->where('id_toma', $request->id_exacto);
        }
        // 3. Búsqueda normal de texto libre desde la barra de búsqueda de la vista
        else if ($request->has('buscar') && $request->buscar != '') {
            $busqueda = $request->buscar;
            $query->where(function($q) use ($busqueda) {
                $q->where('id_toma', 'LIKE', "%{$busqueda}%")
                  ->orWhere('no_medidor', 'LIKE', "%{$busqueda}%")
                  ->orWhere('clave_catastral', 'LIKE', "%{$busqueda}%")
                  ->orWhere('calle', 'LIKE', "%{$busqueda}%")
                  ->orWhereHas('usuario', function($subQ) use ($busqueda) {
                      $subQ->where('razon_social', 'LIKE', "%{$busqueda}%")
                           ->orWhere('apellidos', 'LIKE', "%{$busqueda}%")
                           ->orWhere('no_ciudadano', 'LIKE', "%{$busqueda}%");
                  });
            });
        }

        // 4. Capturar y aplicar los filtros del menú desplegable
        $filtro = $request->query('filtro', 'recientes');

        switch ($filtro) {
            case 'antiguos':
                $query->orderBy('id_toma', 'asc');
                break;
            case 'medido':
                $query->where('calculo_fijo', 0)->orderBy('id_toma', 'desc');
                break;
            case 'cuota_fija':
                $query->where('calculo_fijo', 1)->orderBy('id_toma', 'desc');
                break;
            case 'activos':
                $query->where('estatus', 'ACTIVO')->orderBy('id_toma', 'desc');
                break;
            case 'baja':
                $query->where('estatus', 'BAJA')->orderBy('id_toma', 'desc');
                break;
                
            // NUEVOS CASOS DE SOLVENCIA
            case 'con_adeudo':
                // Traer tomas que TENGAN al menos una factura en estado PENDIENTE
                $query->whereHas('historialPagos', function($q) {
                    $q->where('estatus_pago', 'PENDIENTE');
                })->orderBy('id_toma', 'desc');
                break;
            case 'al_corriente':
                // Traer tomas que NO TENGAN facturas en estado PENDIENTE
                $query->whereDoesntHave('historialPagos', function($q) {
                    $q->where('estatus_pago', 'PENDIENTE');
                })->orderBy('id_toma', 'desc');
                break;

            case 'recientes':
            default:
                $query->orderBy('id_toma', 'desc');
                break;
        }

        // 5. Paginar y conservar todos los parámetros en la URL para evitar que se borren
        $tomas = $query->paginate(15)->appends([
            'buscar' => $request->buscar,
            'filtro' => $filtro,
            'id_exacto' => $request->id_exacto
        ]);

        return view('usuarios.tomas_agua', compact('tomas', 'filtro'));


    }

    public function buscarUsuarios(Request $request)
    {
        $term = $request->input('q'); // Obtener el término de búsqueda desde la solicitud
        
        $usuarios = usuarios::where('razon_social', 'LIKE', "%{$term}%")
            ->orWhere('apellidos', 'LIKE', "%{$term}%")
            ->orWhere('no_ciudadano', 'LIKE', "%{$term}%")
            ->limit(15)
            ->get();

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id'      => 'required|exists:usuarios,id_usuario',
            'id_localidad'    => 'required|exists:localidades,id_localidad',
            'id_tipo_predio'  => 'required|exists:tipo_predio,id_tipo_predio',
            'calle'           => 'required|string|max:150',
            'numero_exterior' => 'required|string|max:20',
            'calculo_fijo'    => 'required|boolean',
            'municipio'       => 'nullable|string|max:100',
            'estatus'         => 'required|string|in:ACTIVO,BAJA',
        ]);

        $calleLimpia = trim(strtoupper($request->calle)); 
        $numExtLimpio = trim(strtoupper($request->numero_exterior));
        $numIntLimpio = trim(strtoupper($request->numero_interior ?? ''));

        $existeDomicilio = TomasAgua::whereRaw('UPPER(TRIM(calle)) = ?', [$calleLimpia])
            ->whereRaw('UPPER(TRIM(numero_exterior)) = ?', [$numExtLimpio])
            ->whereRaw('COALESCE(UPPER(TRIM(numero_interior)), "") = ?', [$numIntLimpio])
            ->exists();

        if ($existeDomicilio) {
            return redirect()->back()->withInput()->with('error', 'El domicilio exacto ya se encuentra registrado en otra toma de agua.');
        }

        DB::transaction(function () use ($request) {
            $data = $request->all();
            $data['municipio'] = $request->municipio ? strtoupper(trim($request->municipio)) : 'ACATIC';
            
            if (isset($data['calculo_fijo']) && $data['calculo_fijo'] == '1') {
                $data['no_medidor'] = null;
            }

            $toma = TomasAgua::create($data);
            DB::table('facturas')->where('toma_id', $toma->id_toma)->delete();
        });

        return redirect()->route('tomas.index')->with('success', 'El contrato de la toma de agua ha sido registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'usuario_id'      => 'required|exists:usuarios,id_usuario',
            'id_localidad'    => 'required|exists:localidades,id_localidad',
            'id_tipo_predio'  => 'required|exists:tipo_predio,id_tipo_predio',
            'calle'           => 'required|string|max:150',
            'numero_exterior' => 'required|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'calculo_fijo'    => 'required|boolean',
            'municipio'       => 'nullable|string|max:100',
            'estatus'         => 'required|string|in:ACTIVO,BAJA',
        ]);

        $calleLimpia  = trim(strtoupper($request->calle));
        $numExtLimpio = trim(strtoupper($request->numero_exterior));
        $numIntLimpio = $request->filled('numero_interior') ? trim(strtoupper($request->numero_interior)) : null;

        // Verificar que el nuevo domicilio no choque con OTRA toma diferente
        $existeDomicilio = TomasAgua::where('id_toma', '!=', $id)
            ->whereRaw('UPPER(TRIM(calle)) = ?', [$calleLimpia])
            ->whereRaw('UPPER(TRIM(numero_exterior)) = ?', [$numExtLimpio])
            ->whereRaw('COALESCE(UPPER(TRIM(numero_interior)), "") = ?', [$numIntLimpio ?? ''])
            ->exists();

        if ($existeDomicilio) {
            return redirect()->back()->with('error', 'Ese domicilio exacto ya está registrado en otra toma de agua.');
        }

        $toma = TomasAgua::findOrFail($id);

        $datosActualizar = [
            'usuario_id'      => $request->usuario_id,
            'pozo_id'         => $request->pozo_id ?: null,
            'id_localidad'    => $request->id_localidad,
            'id_tipo_predio'  => $request->id_tipo_predio,
            'calle'           => $calleLimpia,
            'numero_exterior' => $numExtLimpio,
            'numero_interior' => $numIntLimpio,
            'clave_catastral' => $request->filled('clave_catastral') ? trim(strtoupper($request->clave_catastral)) : null,
            'calculo_fijo'    => $request->calculo_fijo,
            'no_medidor'      => ($request->calculo_fijo == '1') ? null : trim(strtoupper($request->no_medidor)),
            'municipio'       => $request->filled('municipio') ? trim(strtoupper($request->municipio)) : 'ACATIC',
            'estatus'         => $request->estatus,
        ];

        $toma->update($datosActualizar);

        return redirect()->route('tomas.index')->with('success', 'La toma de agua ha sido actualizada correctamente.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            DB::table('facturas')->where('toma_id', $id)->delete();
            TomasAgua::where('id_toma', $id)->delete();
        });

        return redirect()->route('tomas.index')->with('success', 'La toma de agua ha sido eliminada correctamente.');
    }

    public function obtenerHistorial($id_toma)
    {
        try {
            $facturas = DB::table('facturas')
                ->leftJoin('pagos_historial', 'facturas.id_factura', '=', 'pagos_historial.factura_id')
                ->where('facturas.toma_id', $id_toma)
                ->select(
                    'facturas.id_factura',
                    'facturas.toma_id',
                    'facturas.no_factura',
                    'facturas.cuota_base_agua',
                    'facturas.saneamiento',
                    'facturas.infraestructura',
                    'facturas.total_factura',
                    'facturas.periodo_anio',
                    'facturas.periodo_mes',
                    'facturas.estatus_pago',
                    DB::raw('MAX(pagos_historial.fecha_pago) as fecha_pago')
                )
                ->groupBy(
                    'facturas.id_factura',
                    'facturas.toma_id',
                    'facturas.no_factura',
                    'facturas.cuota_base_agua',
                    'facturas.saneamiento',
                    'facturas.infraestructura',
                    'facturas.total_factura',
                    'facturas.periodo_anio',
                    'facturas.periodo_mes',
                    'facturas.estatus_pago'
                )
                ->orderBy('facturas.periodo_anio', 'desc')
                ->orderBy('facturas.id_factura', 'desc')
                ->get();

            $tieneAdeudo = $facturas->contains(function ($factura) {
                return strtoupper($factura->estatus_pago) === 'PENDIENTE';
            });

            $historial = $facturas->map(function($factura) {
                return [
                    'periodo' => $factura->periodo_anio . ' (' . $factura->periodo_mes . ')',
                    'concepto' => 'Factura #' . ($factura->no_factura ?? $factura->id_factura),
                    'monto_agua' => number_format($factura->cuota_base_agua, 2),
                    'monto_saneamiento' => number_format($factura->saneamiento, 2),
                    'monto_infraestructura' => number_format($factura->infraestructura, 2),
                    'descuento' => '0.00',
                    'total' => number_format($factura->total_factura, 2),
                    'fecha_pago' => $factura->fecha_pago ? date('d/M/Y', strtotime($factura->fecha_pago)) : 'PENDIENTE DE PAGO',
                    'estatus' => strtoupper($factura->estatus_pago)
                ];
            });

            return response()->json([
                'tiene_adeudo' => $tieneAdeudo,
                'pagos' => $historial
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}