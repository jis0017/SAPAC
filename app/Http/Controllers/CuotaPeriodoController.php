<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CuotaPeriodo;
use App\Models\TarifaConfig;
use App\Models\TipoPredio;
use App\Models\Localidades;

class CuotaPeriodoController extends Controller
{
    public function index()
    {
        $cuotas = CuotaPeriodo::with(['tipoPredio', 'localidad'])
            ->orderBy('anio', 'desc')
            ->orderBy('id_tipo_predio', 'asc')
            ->get();

        $tarifasMedidas = TarifaConfig::with(['tipoPredio'])
            ->orderBy('anio_vigencia', 'desc')
            ->orderBy('id_tipo_predio', 'asc')
            ->orderBy('limite_inferior', 'asc')
            ->get();

        $tipoPredios = TipoPredio::orderBy('id_tipo_predio', 'asc')->get();
        $localidades = Localidades::all();

        return view('configuracion.tarifas', compact('cuotas', 'tarifasMedidas', 'tipoPredios', 'localidades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'anio'                 => 'required|integer',
            'id_tipo_predio'       => 'required|exists:tipo_predio,id_tipo_predio',
            'id_localidad'         => 'nullable|exists:localidades,id_localidad',
            'cuota_mensual'        => 'required|numeric',
            'saneamiento_mensual'  => 'required|numeric',
            'otros_usos_mensual'   => 'required|numeric',
            'porcentaje_descto1'   => 'nullable|numeric',
            'porcentaje_descto2'   => 'nullable|numeric',
            'costo_nuevo_contrato' => 'required|numeric',
            'costo_reconexion'     => 'required|numeric',
        ]);

        $data = $request->all();

        $cuotaMes = floatval($data['cuota_mensual']);
        $saneamientoMes = floatval($data['saneamiento_mensual']);
        $otrosUsosMes = floatval($data['otros_usos_mensual']);

        $totalAnualSinDesc = ($cuotaMes + $saneamientoMes + $otrosUsosMes) * 12;
        $data['total_anual_sin_descuento'] = round($totalAnualSinDesc, 2);

        $pDesc1 = floatval($data['porcentaje_descto1'] ?? 15.00);
        $data['porcentaje_descto1'] = $pDesc1;
        $data['total_anual_descto1'] = round($totalAnualSinDesc - ($totalAnualSinDesc * ($pDesc1 / 100)), 2);

        $pDesc2 = floatval($data['porcentaje_descto2'] ?? 50.00);
        $data['porcentaje_descto2'] = $pDesc2;
        $data['total_anual_descto2'] = round($totalAnualSinDesc - ($totalAnualSinDesc * ($pDesc2 / 100)), 2);

        CuotaPeriodo::create($data);

        return redirect()->route('configuracion.tarifas')->with('success', 'La cuota y periodo anual han sido calculados y registrados correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'anio'                 => 'required|integer',
            'id_tipo_predio'       => 'required|exists:tipo_predio,id_tipo_predio',
            'id_localidad'         => 'nullable|exists:localidades,id_localidad',
            'cuota_mensual'        => 'required|numeric',
            'saneamiento_mensual'  => 'required|numeric',
            'otros_usos_mensual'   => 'required|numeric',
            'porcentaje_descto1'   => 'nullable|numeric',
            'porcentaje_descto2'   => 'nullable|numeric',
            'costo_nuevo_contrato' => 'required|numeric',
            'costo_reconexion'     => 'required|numeric',
        ]);

        $cuota = CuotaPeriodo::findOrFail($id);
        $data = $request->all();

        $cuotaMes = floatval($data['cuota_mensual']);
        $saneamientoMes = floatval($data['saneamiento_mensual']);
        $otrosUsosMes = floatval($data['otros_usos_mensual']);

        $totalAnualSinDesc = ($cuotaMes + $saneamientoMes + $otrosUsosMes) * 12;
        $data['total_anual_sin_descuento'] = round($totalAnualSinDesc, 2);

        $pDesc1 = floatval($data['porcentaje_descto1'] ?? 15.00);
        $data['porcentaje_descto1'] = $pDesc1;
        $data['total_anual_descto1'] = round($totalAnualSinDesc - ($totalAnualSinDesc * ($pDesc1 / 100)), 2);

        $pDesc2 = floatval($data['porcentaje_descto2'] ?? 50.00);
        $data['porcentaje_descto2'] = $pDesc2;
        $data['total_anual_descto2'] = round($totalAnualSinDesc - ($totalAnualSinDesc * ($pDesc2 / 100)), 2);

        $cuota->update($data);

        return redirect()->route('configuracion.tarifas')->with('success', 'La cuota y periodo anual han sido recalculados y actualizados correctamente.');
    }

    public function destroy($id)
    {
        $cuota = CuotaPeriodo::findOrFail($id);
        $cuota->delete();

        return redirect()->route('configuracion.tarifas')->with('success', 'La cuota ha sido eliminada correctamente.');
    }
}