<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoPipa;
use App\Models\TomasAgua;
use App\Models\Trabajadores;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PedidoPipaController extends Controller
{
    public function index(Request $request)
    {
        $query = PedidoPipa::with(['toma.usuario', 'trabajador', 'pozo']);

        if ($request->filled('buscar')) { //
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('id_pedido', 'LIKE', "%{$buscar}%")
                  ->orWhere('solicitante', 'LIKE', "%{$buscar}%")
                  ->orWhere('telefono', 'LIKE', "%{$buscar}%")
                  ->orWhere('domicilio_entrega', 'LIKE', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado_entrega', $request->estado);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_entrega', $request->fecha);
        }

        $pedidos = $query->orderBy('fecha_entrega', 'desc')->paginate(15)->appends($request->all());

        // Filtrar exclusivamente trabajadores que sean Piperos
        $choferes = Trabajadores::where('activo', 1)
            ->whereHas('tipoTrabajador', function($q) {
                $q->where('tipo_trabajador', 'LIKE', '%Pipero%');
            })->get();

        $pozos = DB::table('ubicacion_pozos')->where('estado', 'ACTIVO')->get();

        return view('pedidos.pipas', compact('pedidos', 'choferes', 'pozos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'toma_id'           => 'nullable|exists:tomas_agua,id_toma',
            'id_trabajador'     => 'nullable|exists:trabajadores,id_trabajador',
            'capacidad_litros'  => 'required|integer|min:1000',
            'solicitante'       => 'required|string|max:255',
            'domicilio_entrega' => 'required|string|max:255',
            'telefono'          => 'required|string|max:10',
            'pozo_id'           => 'nullable|integer',
            'fecha_entrega'     => 'required|date',
            'observaciones'     => 'nullable|string|max:500',
        ]);

        PedidoPipa::create([
            'toma_id'           => $request->toma_id,
            'id_trabajador'     => $request->id_trabajador,
            'pozo_id'           => $request->pozo_id,
            'capacidad_litros'  => $request->capacidad_litros,
            'solicitante'       => strtoupper(trim($request->solicitante)),
            'domicilio_entrega' => strtoupper(trim($request->domicilio_entrega)),
            'telefono'          => trim($request->telefono),
            'observaciones'     => $request->observaciones,
            'estado_entrega'    => 'Pendiente',
            'fecha_pedido'      => now(),
            'fecha_entrega'     => $request->fecha_entrega,
        ]);

        return redirect()->route('pedidos.pipas')->with('success', 'Solicitud de pipa registrada y asignada exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $pedido = PedidoPipa::findOrFail($id);

        // Si la petición viene del modal rápido de "Estatus de Ruta"
        if ($request->has('estado_entrega') && !$request->has('solicitante')) {
            $request->validate([
                'estado_entrega' => 'required|string',
                'id_trabajador'  => 'nullable|exists:trabajadores,id_trabajador',
                'pozo_id'        => 'nullable|integer',
            ]);

            $pedido->update([
                'estado_entrega' => $request->estado_entrega,
                'id_trabajador'  => $request->id_trabajador,
                'pozo_id'        => $request->pozo_id,
            ]);
            
            return redirect()->route('pedidos.pipas')->with('success', 'Estatus logístico actualizado correctamente.');
        }

        // Si la petición viene del modal completo de "Editar Pedido"
        $request->validate([
            'solicitante'       => 'required|string|max:255',
            'domicilio_entrega' => 'required|string|max:255',
            'telefono'          => 'required|string|max:10',
            'capacidad_litros'  => 'required|integer',
            'fecha_entrega'     => 'required|date',
            'id_trabajador'     => 'nullable|exists:trabajadores,id_trabajador',
            'pozo_id'           => 'nullable|integer',
        ]);

        $pedido->update([
            'solicitante'       => strtoupper(trim($request->solicitante)),
            'domicilio_entrega' => strtoupper(trim($request->domicilio_entrega)),
            'telefono'          => trim($request->telefono),
            'capacidad_litros'  => $request->capacidad_litros,
            'id_trabajador'     => $request->id_trabajador,
            'pozo_id'           => $request->pozo_id,
            // Solo actualizamos el estado aquí si se incluyó en el formulario completo
            'estado_entrega'    => $request->has('estado_entrega') ? $request->estado_entrega : $pedido->estado_entrega,
        ]);

        return redirect()->route('pedidos.pipas')->with('success', 'Pedido de pipa actualizado correctamente.');
    }

    public function destroy($id)
    {
        $pedido = PedidoPipa::findOrFail($id);
        $pedido->delete();

        return redirect()->route('pedidos.pipas')->with('success', 'Solicitud de pipa eliminada correctamente.');
    }

    public function verificarSolvencia(Request $request)
    {
        $query = trim($request->input('query'));
        
        if (empty($query)) {
            return response()->json(['encontrado' => false, 'message' => 'Ingresa un término de búsqueda.']);
        }

        $tomaQuery = TomasAgua::with(['usuario', 'localidad', 'tipoPredio']);

        // 1. Separar lo que escribió el usuario por palabras
        $terminos = array_values(array_filter(explode(' ', $query)));

        // 2. Por CADA palabra, buscar si existe en alguno de los campos solicitados
        foreach ($terminos as $termino) {
            $tomaQuery->where(function($q) use ($termino) {
                $q->where('id_toma', 'LIKE', "%{$termino}%")
                  ->orWhere('no_medidor', 'LIKE', "%{$termino}%")
                  ->orWhere('calle', 'LIKE', "%{$termino}%")
                  ->orWhere('numero_exterior', 'LIKE', "%{$termino}%")
                  ->orWhere('numero_interior', 'LIKE', "%{$termino}%")
                  ->orWhereHas('usuario', function($uQ) use ($termino) {
                      $uQ->where('razon_social', 'LIKE', "%{$termino}%")
                         ->orWhere('apellidos', 'LIKE', "%{$termino}%");
                  });
            });
        }

        // 3. Traer TODAS las tomas que coincidan (para que elijan si una persona tiene varias)
        $tomas = $tomaQuery->limit(15)->get();

        if ($tomas->isEmpty()) {
            return response()->json(['encontrado' => false, 'message' => 'No se encontró ninguna toma con esos datos.']);
        }

        // 4. Formatear la lista de resultados para enviarla a JavaScript
        $resultados = $tomas->map(function($toma) {
            $totalDeuda = DB::table('facturas')
                ->where('toma_id', $toma->id_toma)
                ->where('estatus_pago', 'PENDIENTE')
                ->sum('total_factura');

            // Solo nombre / razón social sin apellidos duplicados
            $nombreCiudadano = trim($toma->usuario->razon_social ?? '');
            
            // Construir domicilio con interior y exterior
            $calle = !empty($toma->calle) ? $toma->calle : ($toma->direccion_anterior ?? '');
            $direccion = trim($calle . ' #' . $toma->numero_exterior . (!empty($toma->numero_interior) ? ' INT. ' . $toma->numero_interior : '') . ', ' . ($toma->localidad->nombre_localidad ?? 'Cabecera Municipal'));

            return [
                'toma_id'            => $toma->id_toma,
                'ciudadano'          => $nombreCiudadano ?: 'PÚBLICO EN GENERAL',
                'telefono'           => $toma->usuario->telefono ?? '',
                'tipo_propiedad'     => $toma->tipoPredio->nombre_predio ?? 'DOMÉSTICO',
                'direccion_completa' => strtoupper($direccion),
                'al_corriente'       => ($totalDeuda <= 0),
                'total_deuda'        => $totalDeuda
            ];
        });

        return response()->json([
            'encontrado' => true,
            'resultados' => $resultados
        ]);
    }

    public function misPedidosMobile(Request $request)
    {
        $user = $request->user();

        // Cargamos la toma y TODOS los pedidos asignados a este pipero, 
        // sin importar si están pendientes, en camino, entregados o cancelados.
        $pedidos = PedidoPipa::with(['toma'])
            ->where('id_trabajador', $user->id_trabajador)
            // Se quitó el whereIn de estados para que lleguen al historial
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'pedidos' => $pedidos
        ]);
    }

    public function actualizarEstadoMobile(Request $request, $id)
{
    $request->validate([
        'estado_entrega' => 'required|in:Pendiente,En camino,Entregado,Cancelado',
        'latitud'        => 'nullable|numeric',
        'longitud'       => 'nullable|numeric',
    ]);

    $user = $request->user();
    $pedido = PedidoPipa::where('id_pedido', $id)
        ->where('id_trabajador', $user->id_trabajador)
        ->first();

    if (!$pedido) {
        return response()->json(['success' => false, 'message' => 'Pedido no encontrado.'], 404);
    }

    // Actualizamos el estatus del pedido
    $pedido->update([
        'estado_entrega' => $request->estado_entrega,
        'fecha_entrega'  => $request->estado_entrega === 'Entregado' ? now() : $pedido->fecha_entrega,
    ]);

    // SI EL PEDIDO TIENE UNA TOMA ASOCIADA Y EL CELULAR MANDÓ COORDENADAS:
    // Guardamos la ubicación de forma permanente en la toma de agua ("Aprende la ubicación")
    if ($request->filled('latitud') && $request->filled('longitud') && !empty($pedido->toma_id)) {
        \App\Models\TomasAgua::where('id_toma', $pedido->toma_id)->update([
            'latitud'  => $request->latitud,
            'longitud' => $request->longitud,
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Estatus actualizado y ubicación guardada en el padrón.',
        'pedido'  => $pedido
    ]);
}
}