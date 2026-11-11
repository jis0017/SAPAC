<?php

namespace App\Http\Controllers;

use App\Models\usuarios;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        // 1. Iniciar la consulta base con sus relaciones
        $query = usuarios::with('tomas');

        // 2. Búsqueda exacta desde el buscador global (tiene prioridad)
        if ($request->has('id_exacto') && $request->id_exacto != '') {
            $query->where('no_ciudadano', $request->id_exacto);
        } 
        // 3. Búsqueda normal desde la barra de búsqueda de la vista
        else if ($request->has('buscar') && $request->buscar != '') { 
            $busqueda = $request->buscar;
            $query->where(function($q) use ($busqueda) {
                  $q->where('razon_social', 'LIKE', "%{$busqueda}%")
                    ->orWhere('no_ciudadano', 'LIKE', "%{$busqueda}%")
                    ->orWhere('rfc', 'LIKE', "%{$busqueda}%");
            });
        }

        // 4. Capturar y aplicar la lógica de los filtros de ordenamiento
        $filtro = $request->query('filtro', 'recientes');

        switch ($filtro) {
            case 'alfabetico_az':
                $query->orderBy('razon_social', 'asc')->orderBy('apellidos', 'asc');
                break;
            case 'alfabetico_za':
                $query->orderBy('razon_social', 'desc')->orderBy('apellidos', 'desc');
                break;
            case 'mas_tomas':
                $query->withCount('tomas')->orderBy('tomas_count', 'desc');
                break;
            case 'sin_tomas':
                $query->doesntHave('tomas')->orderBy('id_usuario', 'desc');
                break;
            case 'antiguos':
                $query->orderBy('id_usuario', 'asc');
                break;
            case 'recientes':
            default:
                $query->orderBy('id_usuario', 'desc');
                break;
        }

        // 5. Paginar y conservar todos los parámetros en la URL al cambiar de página
        $usuarios = $query->paginate(15)->appends([
             'filtro' => $filtro, 
             'buscar' => $request->buscar,
             'id_exacto' => $request->id_exacto 
        ]);

        // 6. Retornar la vista una sola vez
        return view('usuarios.index_usuarios', compact('usuarios', 'filtro'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'razon_social' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:13',
            'telefono' => 'nullable|string|max:20',
        ]);

        $ultimoNumero = usuarios::max('no_ciudadano');
        $nuevoNumeroCiudadano = $ultimoNumero ? $ultimoNumero + 1 : 1; 

        $usuario = new usuarios();
        $usuario->no_ciudadano = $nuevoNumeroCiudadano;
        $usuario->razon_social = mb_strtoupper($request->razon_social, 'UTF-8');
        $usuario->apellidos = $request->apellidos ? mb_strtoupper($request->apellidos, 'UTF-8') : null;
        $usuario->rfc = $request->rfc ? mb_strtoupper($request->rfc, 'UTF-8') : null;
        $usuario->telefono = $request->telefono;
        $usuario->save();

        $folioFormateado = str_pad($nuevoNumeroCiudadano, 5, '0', STR_PAD_LEFT);
        
        return redirect()->route('usuarios.index')->with('success', 'Ciudadano registrado exitosamente con el Folio #' . $folioFormateado);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'razon_social' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:13',
            'telefono' => 'nullable|string|max:20',
        ]);

        $usuario = usuarios::findOrFail($id);
        $usuario->razon_social = mb_strtoupper($request->razon_social, 'UTF-8');
        $usuario->apellidos = $request->apellidos ? mb_strtoupper($request->apellidos, 'UTF-8') : null;
        $usuario->rfc = $request->rfc ? mb_strtoupper($request->rfc, 'UTF-8') : null;
        $usuario->telefono = $request->telefono;
        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Los datos del ciudadano han sido actualizados con éxito.');
    }

    public function destroy($id)
    {
        $usuario = usuarios::findOrFail($id);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'El ciudadano ha sido dado de baja correctamente.');
    }

    /**
     * Omoheñói peteĩ JSON ohechaukáva umi tomas oĩva peteĩ usuario-pe guarã (Ajax)
     */
    public function obtenerTomas($id)
    {
        try {
            $usuario = usuarios::with('tomas.tipoPredio')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'usuario' => $usuario->razon_social . ' ' . ($usuario->apellidos ?? ''),
                'tomas' => $usuario->tomas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'No se encontraron las tomas asociadas.'
            ], 404);
        }
    }
}