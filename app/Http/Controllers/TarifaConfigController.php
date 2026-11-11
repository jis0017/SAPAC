<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TarifaConfig;

class TarifaConfigController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'anio_vigencia'      => 'required|integer',
            'id_tipo_predio'     => 'required|exists:tipo_predio,id_tipo_predio',
            'limite_inferior'    => 'required|integer|min:0',
            'limite_superior'    => 'required|integer|gte:limite_inferior',
            'costo_metro_cubico' => 'required|numeric|min:0',
            'consumo_minimo'     => 'required|integer|min:0',
        ]);

        $data = $request->all();

        $limInf = intval($data['limite_inferior']);
        $limSup = intval($data['limite_superior']);
        $costoM3 = floatval($data['costo_metro_cubico']);

        // 1. Cálculo automático del Costo del Rango
        $metrosRango = max(0, $limSup - $limInf);
        $costoRango = round($metrosRango * $costoM3, 2);
        $data['costo_rango'] = $costoRango;

        // 2. Cálculo automático del Costo Acumulado (busca el rango anterior del mismo predio y año)
        $rangoAnterior = TarifaConfig::where('anio_vigencia', $data['anio_vigencia'])
            ->where('id_tipo_predio', $data['id_tipo_predio'])
            ->where('limite_superior', '<=', $limInf)
            ->orderBy('limite_superior', 'desc')
            ->first();

        $acumuladoPrevio = $rangoAnterior ? floatval($rangoAnterior->costo_acumulado) : 0.00;
        $data['costo_acumulado'] = round($acumuladoPrevio + $costoRango, 2);

        TarifaConfig::create($data);

        return redirect()->route('configuracion.tarifas')
            ->with('tab_activo', 'medido')
            ->with('success', 'El rango de tarifa por medidor ha sido calculado y registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'anio_vigencia'      => 'required|integer',
            'id_tipo_predio'     => 'required|exists:tipo_predio,id_tipo_predio',
            'limite_inferior'    => 'required|integer|min:0',
            'limite_superior'    => 'required|integer|gte:limite_inferior',
            'costo_metro_cubico' => 'required|numeric|min:0',
            'consumo_minimo'     => 'required|integer|min:0',
        ]);

        $tarifa = TarifaConfig::findOrFail($id);
        $data = $request->all();

        $limInf = intval($data['limite_inferior']);
        $limSup = intval($data['limite_superior']);
        $costoM3 = floatval($data['costo_metro_cubico']);

        // Recalcular Costo del Rango
        $metrosRango = max(0, $limSup - $limInf);
        $costoRango = round($metrosRango * $costoM3, 2);
        $data['costo_rango'] = $costoRango;

        // Recalcular Costo Acumulado
        $rangoAnterior = TarifaConfig::where('anio_vigencia', $data['anio_vigencia'])
            ->where('id_tipo_predio', $data['id_tipo_predio'])
            ->where('id_tarifa', '!=', $id)
            ->where('limite_superior', '<=', $limInf)
            ->orderBy('limite_superior', 'desc')
            ->first();

        $acumuladoPrevio = $rangoAnterior ? floatval($rangoAnterior->costo_acumulado) : 0.00;
        $data['costo_acumulado'] = round($acumuladoPrevio + $costoRango, 2);

        $tarifa->update($data);

        return redirect()->route('configuracion.tarifas')
            ->with('tab_activo', 'medido')
            ->with('success', 'El rango de tarifa por medidor ha sido recalculado y actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tarifa = TarifaConfig::findOrFail($id);
        $tarifa->delete();

        return redirect()->route('configuracion.tarifas')
            ->with('tab_activo', 'medido')
            ->with('success', 'El rango de tarifa por medidor ha sido eliminado correctamente.');
    }
}