<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Trabajadores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('sesion.login');
    }

    // =========================================================
    // 1. LOGIN PARA EL SISTEMA WEB (Sesiones y Vistas Blade)
    // =========================================================
    public function login(Request $request)
    {
        $request->validate([
            'usuario_trabajador' => 'required|string|max:50',
            'password' => 'required|string',
        ]);  

        $throttleKey = mb_strtolower($request->input('usuario_trabajador')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'usuario_trabajador' => ["Demasiados intentos de inicio de sesión. Por favor intente de nuevo en {$seconds} segundos."],
            ]);
        }

        $credenciales = [
            'usuario_trabajador' => $request->input('usuario_trabajador'),
            'password' => $request->input('password'),
            'activo' => 1,
        ];

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($throttleKey);

        throw ValidationException::withMessages([
            'usuario_trabajador' => ['Las credenciales proporcionadas no coinciden con nuestros registros o la cuenta está inactiva.'],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // =========================================================
    // 2. LOGIN PARA LA APP MÓVIL EN FLUTTER (JSON + Sanctum)
    // =========================================================
    public function apiLogin(Request $request)
    {
        $request->validate([
            'usuario_trabajador' => 'required|string|max:50',
            'password'           => 'required|string',
        ]);

        // Misma protección contra fuerza bruta que tienes en la web
        $throttleKey = 'mobile|' . mb_strtolower($request->input('usuario_trabajador')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos."
            ], 429);
        }

        $trabajador = Trabajadores::with('tipoTrabajador')
            ->where('usuario_trabajador', $request->usuario_trabajador)
            ->first();

        if (!$trabajador || !Hash::check($request->password, $trabajador->password)) {
            RateLimiter::hit($throttleKey);
            return response()->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos.'
            ], 401);
        }

        if (!$trabajador->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta se encuentra inactiva.'
            ], 403);
        }

        // Validar que solo entren Piperos (o Ingenieros para tus pruebas)
        if (!$trabajador->tieneRol(['Pipero', 'Ingeniero'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso exclusivo para operadores de pipa.'
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        // Generar token para Flutter
        $token = $trabajador->createToken('sapac-pipero-app')->plainTextToken;

        return response()->json([
            'success'    => true,
            'token'      => $token,
            'trabajador' => [
                'id'     => $trabajador->id_trabajador,
                'nombre' => $trabajador->nombre,
                'rol'    => $trabajador->tipoTrabajador->tipo_trabajador ?? 'PIPERO',
            ]
        ]);
    }

    public function apiLogout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['success' => true, 'message' => 'Sesión cerrada']);
    }

}