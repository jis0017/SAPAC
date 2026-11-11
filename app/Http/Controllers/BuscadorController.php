<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\usuarios;
use App\Models\TomasAgua; // Descomentado e integrado

class BuscadorController extends Controller
{

    public function buscar(Request $request)
    {
        $termino = $request->query('q');

        if (!$termino || strlen($termino) < 2) {
            return response()->json([]);
        }


        $usuarios = usuarios::whereRaw("CONCAT(razon_social, ' ', IFNULL(apellidos, '')) LIKE ?", ["%{$termino}%"])
            ->orWhere('no_ciudadano', 'LIKE', "%{$termino}%")
            ->orWhere('rfc', 'LIKE', "%{$termino}%")
            ->limit(15)
            ->get()
            ->map(function($u) {
                return [
                    'tipo' => 'Ciudadano',
                    'icono' => 'bi-person-fill',
                    'color' => 'text-primary',
                    'titulo' => trim($u->razon_social . ' ' . $u->apellidos),
                    'subtitulo' => 'Folio: ' . $u->no_ciudadano . ' | RFC: ' . ($u->rfc ?? 'N/A'),
                    // CAMBIO AQUÍ: Redirigir a usuarios.index con el id_exacto (el no_ciudadano)
                    'url' => route('usuarios.index', ['id_exacto' => $u->no_ciudadano]) 
                ];
            });

        // 2. Buscar en Tomas de Agua
        $tomas = TomasAgua::with('usuario')
            ->where('id_toma', 'LIKE', "%{$termino}%")
            ->orWhere('calle', 'LIKE', "%{$termino}%")
            ->orWhere('numero_exterior', 'LIKE', "%{$termino}%")
            ->limit(15)
            ->get()
            ->map(function($t) {
                $nombreTitular = $t->usuario ? trim($t->usuario->razon_social . ' ' . $t->usuario->apellidos) : 'Sin titular asignado';
                return [
                    'tipo' => 'Toma de Agua',
                    'icono' => 'bi-droplet-half',
                    'color' => 'text-info',
                    'titulo' => 'Toma #' . $t->id_toma . ' - ' . $t->calle . ' #' . $t->numero_exterior,
                    'subtitulo' => 'Titular: ' . $nombreTitular,
                    'url' => route('tomas.index', ['id_exacto' => $t->id_toma]) 
                ];
            });

        $resultados = $usuarios->concat($tomas);
        return response()->json($resultados);
    }

}
