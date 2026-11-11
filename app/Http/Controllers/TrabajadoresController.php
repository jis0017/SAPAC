<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajadores;
use App\Models\TipoTrabajador;
use Illuminate\Support\Facades\Hash;

class TrabajadoresController extends Controller
{
    public function index(Request $request)
    {
        $query = Trabajadores::with('tipoTrabajador');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('usuario_trabajador', 'LIKE', "%{$buscar}%");
            });
        }

        $trabajadores = $query->orderBy('id_trabajador', 'desc')->paginate(15)->appends($request->all());
        $tiposTrabajador = TipoTrabajador::all();

        return view('trabajadores.trabajadores', compact('trabajadores', 'tiposTrabajador'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_tipo_trabajador' => 'required|exists:tipo_trabajador,id_tipo_trabajador',
            'nombre'             => 'required|string|max:100',
            'usuario_trabajador' => 'required|string|max:50|unique:trabajadores,usuario_trabajador',
            'password'           => 'required|string|min:6',
            'activo'             => 'required|boolean',
        ]);

        Trabajadores::create([
            'id_tipo_trabajador' => $request->id_tipo_trabajador,
            'nombre'             => strtoupper(trim($request->nombre)),
            'usuario_trabajador' => trim($request->usuario_trabajador),
            'password'           => Hash::make($request->password),
            'activo'             => $request->activo,
        ]);

        return redirect()->route('trabajadores.trabajadores')->with('success', 'El trabajador ha sido registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $trabajador = Trabajadores::findOrFail($id);

        $request->validate([
            'id_tipo_trabajador' => 'required|exists:tipo_trabajador,id_tipo_trabajador',
            'nombre'             => 'required|string|max:100',
            'usuario_trabajador' => 'required|string|max:50|unique:trabajadores,usuario_trabajador,' . $id . ',id_trabajador',
            'password'           => 'nullable|string|min:6',
            'activo'             => 'required|boolean',
        ]);

        $datosActualizar = [
            'id_tipo_trabajador' => $request->id_tipo_trabajador,
            'nombre'             => strtoupper(trim($request->nombre)),
            'usuario_trabajador' => trim($request->usuario_trabajador),
            'activo'             => $request->activo,
        ];

        if ($request->filled('password')) {
            $datosActualizar['password'] = Hash::make($request->password);
        }

        $trabajador->update($datosActualizar);

        return redirect()->route('trabajadores.trabajadores')->with('success', 'Los datos del trabajador han sido actualizados.');
    }

    public function destroy($id)
    {
        $trabajador = Trabajadores::findOrFail($id);
        $trabajador->delete();

        return redirect()->route('trabajadores.trabajadores')->with('success', 'El trabajador ha sido eliminado del sistema.');
    }

    // --- NUEVOS MÉTODOS PARA TIPOS DE TRABAJADOR ---
    public function storeTipo(Request $request)
    {
        $request->validate([
            'tipo_trabajador' => 'required|string|max:50|unique:tipo_trabajador,tipo_trabajador'
        ]);

        TipoTrabajador::create([
            'tipo_trabajador' => strtoupper(trim($request->tipo_trabajador))
        ]);

        return redirect()->route('trabajadores.trabajadores')->with('success', 'El tipo de trabajador ha sido creado correctamente.');
    }

    public function updateTipo(Request $request, $id)
    {
        $tipo = TipoTrabajador::findOrFail($id);
        
        $request->validate([
            'tipo_trabajador' => 'required|string|max:50|unique:tipo_trabajador,tipo_trabajador,' . $id . ',id_tipo_trabajador'
        ]);

        $tipo->update([
            'tipo_trabajador' => strtoupper(trim($request->tipo_trabajador))
        ]);

        return redirect()->route('trabajadores.trabajadores')->with('success', 'El tipo de trabajador ha sido actualizado.');
    }

    public function destroyTipo($id)
    {
        $tipo = TipoTrabajador::findOrFail($id);

        // Verificar si hay trabajadores asociados a este tipo
        if ($tipo->trabajadores()->count() > 0) {
            return redirect()->route('trabajadores.trabajadores')->with('error', 'No se puede eliminar este tipo de trabajador porque hay trabajadores asociados a él.');
        }

        $tipo->delete();

        return redirect()->route('trabajadores.trabajadores')->with('success', 'El tipo de trabajador ha sido eliminado correctamente.');
    }
}