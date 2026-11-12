<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $usuario = Auth::user();

        // Verifica si la cuenta sigue activa durante la sesión
        if ($usuario->activo == 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('login')->with('error', 'Su cuenta ha sido desactivada.');
        }

        // Normaliza a minúsculas y evita error si tipoTrabajador es null
        $rolUsuario = mb_strtolower(trim($usuario->tipoTrabajador?->tipo_trabajador ?? ''));
        $rolesPermitidos = array_map(fn($r) => mb_strtolower(trim($r)), $roles);

        // Verifica si el tipo de trabajador coincide con alguno de los roles permitidos
        if (!in_array($rolUsuario, $rolesPermitidos, true)) {
            abort(403, 'Acceso denegado: No cuenta con los permisos necesarios.');
        }

        return $next($request);
    }
}