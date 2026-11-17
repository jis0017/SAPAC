<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajadores;
use App\Models\TipoTrabajador;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TrabajadoresController extends Controller
{
    // Roles base del sistema que no deben ser renombrados ni eliminados porque web.php depende de ellos
    private const ROLES_PROTEGIDOS = ['ingeniero', 'secretaria', 'pipero'];

    public function index(Request $request)
    {
        $request->validate([
            'buscar' => 'nullable|string|max:100',
        ]);

        $query = Trabajadores::with('tipoTrabajador');

        if ($request->filled('buscar')) {
            // Escapar comodines SQL (% y _) para evitar búsquedas distorsionadas
            $buscar = addcslashes(trim($request->buscar), '%_');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('usuario_trabajador', 'LIKE', "%{$buscar}%");
            });
        }

        $trabajadores = $query->orderBy('id_trabajador', 'desc')
            ->paginate(15)
            ->appends($request->only('buscar'));

        $tiposTrabajador = TipoTrabajador::all();

        return view('trabajadores.trabajadores', compact('trabajadores', 'tiposTrabajador'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_tipo_trabajador' => 'required|integer|exists:tipo_trabajador,id_tipo_trabajador',
            'nombre'             => 'required|string|max:100',
            'usuario_trabajador' => 'required|string|max:50|unique:trabajadores,usuario_trabajador',
            'password'           => 'required|string|min:6|max:100',
            'activo'             => 'required|boolean',
        ]);

        // Defensa en profundidad: solo un Ingeniero puede asignar el rol de Ingeniero
        $tipoDestino = TipoTrabajador::findOrFail($request->id_tipo_trabajador);
        $rolActual = mb_strtolower(trim(Auth::user()?->tipoTrabajador?->tipo_trabajador ?? ''));
        if (mb_strtolower(trim($tipoDestino->tipo_trabajador)) === 'ingeniero' && $rolActual !== 'ingeniero') {
            abort(403, 'Solo un Ingeniero puede crear cuentas con nivel de Ingeniero.');
        }

        Trabajadores::create([
            'id_tipo_trabajador' => $request->id_tipo_trabajador,
            'nombre'             => mb_strtoupper(trim($request->nombre), 'UTF-8'),
            'usuario_trabajador' => trim($request->usuario_trabajador),
            'password'           => Hash::make($request->password),
            'activo'             => $request->boolean('activo') ? 1 : 0,
        ]);

        return redirect()->route('trabajadores.trabajadores')
            ->with('success', 'El trabajador ha sido registrado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $trabajador = Trabajadores::with('tipoTrabajador')->findOrFail($id);

        $request->validate([
            'id_tipo_trabajador' => 'required|integer|exists:tipo_trabajador,id_tipo_trabajador',
            'nombre'             => 'required|string|max:100',
            'usuario_trabajador' => 'required|string|max:50|unique:trabajadores,usuario_trabajador,' . $id . ',id_trabajador',
            'password'           => 'nullable|string|min:6|max:100',
            'activo'             => 'required|boolean',
        ]);

        $esCuentaPropia = ((int) $trabajador->id_trabajador === (int) Auth::id());
        $esAdminMaestro = (mb_strtolower($trabajador->usuario_trabajador) === 'admin_sapac');
        $cambioRol      = ((int) $trabajador->id_tipo_trabajador !== (int) $request->id_tipo_trabajador);
        $seDesactivo    = !$request->boolean('activo');
        $cambioPassword = $request->filled('password');

        // 1. Evitar que el usuario en sesión se desactive o se quite su propio rol por accidente
        if ($esCuentaPropia && $seDesactivo) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', 'Por seguridad, no puedes desactivar tu propia cuenta mientras estás en sesión.');
        }

        if ($esCuentaPropia && $cambioRol) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', 'Por seguridad, no puedes cambiar tu propio rol mientras estás en sesión.');
        }

        // 2. Proteger la cuenta maestra admin_sapac contra desactivación o degradación de rol
        if ($esAdminMaestro && ($seDesactivo || $cambioRol)) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', 'La cuenta maestra admin_sapac no puede ser desactivada ni cambiada de rol.');
        }

        // 3. Defensa en profundidad: evitar que un rol inferior edite a un Ingeniero o se asigne rol Ingeniero
        $tipoDestino = TipoTrabajador::findOrFail($request->id_tipo_trabajador);
        $rolActual = mb_strtolower(trim(Auth::user()?->tipoTrabajador?->tipo_trabajador ?? ''));
        $rolTrabajadorObjetivo = mb_strtolower(trim($trabajador->tipoTrabajador?->tipo_trabajador ?? ''));

        if ($rolActual !== 'ingeniero' && ($rolTrabajadorObjetivo === 'ingeniero' || mb_strtolower(trim($tipoDestino->tipo_trabajador)) === 'ingeniero')) {
            abort(403, 'No tienes permisos para modificar cuentas con nivel de Ingeniero.');
        }

        $datosActualizar = [
            'id_tipo_trabajador' => $request->id_tipo_trabajador,
            'nombre'             => mb_strtoupper(trim($request->nombre), 'UTF-8'),
            'usuario_trabajador' => $esAdminMaestro ? $trabajador->usuario_trabajador : trim($request->usuario_trabajador),
            'activo'             => $request->boolean('activo') ? 1 : 0,
        ];

        if ($cambioPassword) {
            $datosActualizar['password'] = Hash::make($request->password);
        }

        $trabajador->update($datosActualizar);

        // 4. Revocar tokens móviles (Sanctum) si el trabajador fue desactivado, cambió contraseña o cambió de rol
        if (($seDesactivo || $cambioPassword || $cambioRol) && method_exists($trabajador, 'tokens')) {
            $trabajador->tokens()->delete();
        }

        return redirect()->route('trabajadores.trabajadores')
            ->with('success', 'Los datos del trabajador han sido actualizados.');
    }

    public function destroy($id)
    {
        $trabajador = Trabajadores::with('tipoTrabajador')->findOrFail($id);

        // 1. Impedir que el usuario activo se elimine a sí mismo
        if ((int) $trabajador->id_trabajador === (int) Auth::id()) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', 'No puedes eliminar tu propia cuenta activa.');
        }

        // 2. Impedir eliminar la cuenta maestra del sistema
        if (mb_strtolower($trabajador->usuario_trabajador) === 'admin_sapac') {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', 'Por seguridad, la cuenta maestra del sistema no puede ser eliminada.');
        }

        // 3. Defensa en profundidad: solo un Ingeniero puede eliminar a otro Ingeniero
        $rolActual = mb_strtolower(trim(Auth::user()?->tipoTrabajador?->tipo_trabajador ?? ''));
        $rolObjetivo = mb_strtolower(trim($trabajador->tipoTrabajador?->tipo_trabajador ?? ''));
        if ($rolObjetivo === 'ingeniero' && $rolActual !== 'ingeniero') {
            abort(403, 'No tienes permisos para eliminar una cuenta de Ingeniero.');
        }

        // 4. Revocar tokens móviles antes de eliminar el registro
        if (method_exists($trabajador, 'tokens')) {
            $trabajador->tokens()->delete();
        }

        $trabajador->delete();

        return redirect()->route('trabajadores.trabajadores')
            ->with('success', 'El trabajador ha sido eliminado del sistema.');
    }

    // --- MÉTODOS PARA TIPOS DE TRABAJADOR / ROLES ---
    public function storeTipo(Request $request)
    {
        $request->validate([
            'tipo_trabajador' => 'required|string|max:50|unique:tipo_trabajador,tipo_trabajador'
        ]);

        // Formato Tipo Oración (ej. "Ingeniero", "Secretaria") para mantener consistencia con el Seeder y web.php
        $nombreNormalizado = mb_convert_case(mb_strtolower(trim($request->tipo_trabajador), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        TipoTrabajador::create([
            'tipo_trabajador' => $nombreNormalizado
        ]);

        return redirect()->route('trabajadores.trabajadores')
            ->with('success', 'El tipo de trabajador ha sido creado correctamente.');
    }

    public function updateTipo(Request $request, $id)
    {
        $tipo = TipoTrabajador::findOrFail($id);

        $request->validate([
            'tipo_trabajador' => 'required|string|max:50|unique:tipo_trabajador,tipo_trabajador,' . $id . ',id_tipo_trabajador'
        ]);

        $nombreActualMin = mb_strtolower(trim($tipo->tipo_trabajador), 'UTF-8');
        $nombreNuevoMin  = mb_strtolower(trim($request->tipo_trabajador), 'UTF-8');

        // Proteger los 3 roles base (Ingeniero, Secretaria, Pipero) para no romper los middlewares de web.php
        if (in_array($nombreActualMin, self::ROLES_PROTEGIDOS, true) && $nombreActualMin !== $nombreNuevoMin) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', "El rol base '{$tipo->tipo_trabajador}' está protegido por el sistema y no puede cambiar de nombre.");
        }

        $nombreNormalizado = mb_convert_case($nombreNuevoMin, MB_CASE_TITLE, 'UTF-8');

        $tipo->update([
            'tipo_trabajador' => $nombreNormalizado
        ]);

        return redirect()->route('trabajadores.trabajadores')
            ->with('success', 'El tipo de trabajador ha sido actualizado.');
    }

    public function destroyTipo($id)
    {
        $tipo = TipoTrabajador::findOrFail($id);

        // 1. Impedir eliminar cualquiera de los 3 roles base del sistema
        if (in_array(mb_strtolower(trim($tipo->tipo_trabajador), 'UTF-8'), self::ROLES_PROTEGIDOS, true)) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', "El rol base '{$tipo->tipo_trabajador}' es indispensable para el sistema y no puede eliminarse.");
        }

        // 2. Verificar si hay trabajadores asociados a este tipo
        if ($tipo->trabajadores()->count() > 0) {
            return redirect()->route('trabajadores.trabajadores')
                ->with('error', 'No se puede eliminar este tipo de trabajador porque hay trabajadores asociados a él.');
        }

        $tipo->delete();

        return redirect()->route('trabajadores.trabajadores')
            ->with('success', 'El tipo de trabajador ha sido eliminado correctamente.');
    }
}