<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pozos;

class PozosController extends Controller
{
    public function index()
    {
        $pozos = Pozos::orderBy('nombre_pozo', 'asc')->get();
        return view('pozos.pozos', compact('pozos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_pozo' => 'required|string|max:200|unique:ubicacion_pozos,nombre_pozo',
            'latitud'     => 'required|numeric',
            'longitud'    => 'required|numeric',
            'altitud'     => 'nullable|numeric',
            'estado'      => 'required|string|max:20',
        ]);

        Pozos::create($request->all());

        return redirect()->back()->with('success', 'Pozo registrado con éxito.');
    }

    public function update(Request $request, $id)
    {
        $pozo = Pozos::findOrFail($id);

        $request->validate([
            'nombre_pozo' => 'required|string|max:200|unique:ubicacion_pozos,nombre_pozo,' . $id . ',id_pozo',
            'latitud'     => 'required|numeric',
            'longitud'    => 'required|numeric',
            'altitud'     => 'nullable|numeric',
            'estado'      => 'required|string|max:20',
        ]);

        $pozo->update($request->all());

        return redirect()->back()->with('success', 'Pozo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $pozo = Pozos::findOrFail($id);
        $pozo->delete();

        return redirect()->back()->with('success', 'Pozo eliminado correctamente.');
    }
}