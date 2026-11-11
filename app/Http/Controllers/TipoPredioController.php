<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoPredio;
use App\Models\CuotaPeriodo;
use App\Models\Localidades;

class TipoPredioController extends Controller
{
    public function index()
    {
        $cuotas = CuotaPeriodo::with(['tipoPredio', 'localidad'])->orderBy('anio', 'desc')->get();
        $tipoPredios = TipoPredio::orderBy('id_tipo_predio', 'asc')->get();
        $localidades = Localidades::all();

        return view('configuracion.tarifas', compact('cuotas', 'tipoPredios', 'localidades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_predio' => 'required|string|max:100|unique:tipo_predio,nombre_predio'
        ]);

        TipoPredio::create([
            'nombre_predio' => strtoupper(trim($request->nombre_predio))
        ]);

        return redirect()->route('configuracion.tarifas')->with('success', 'El tipo de predio ha sido registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_predio' => 'required|string|max:100|unique:tipo_predio,nombre_predio,' . $id . ',id_tipo_predio'
        ]);

        $tipoPredio = TipoPredio::findOrFail($id);
        $tipoPredio->update([
            'nombre_predio' => strtoupper(trim($request->nombre_predio))
        ]);

        return redirect()->route('configuracion.tarifas')->with('success', 'El tipo de predio ha sido actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tipoPredio = TipoPredio::findOrFail($id);
        $tipoPredio->delete();

        return redirect()->route('configuracion.tarifas')->with('success', 'El tipo de predio ha sido eliminado correctamente.');
    }
}