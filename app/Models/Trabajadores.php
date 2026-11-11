<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
USE Illuminate\Foundation\Auth\User as Authenticatable;


class Trabajadores extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'trabajadores';
    protected $primaryKey = 'id_trabajador';

    protected $fillable = [
        'id_tipo_trabajador',
        'nombre',
        'usuario_trabajador',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];

    public function tipoTrabajador()
    {
        return $this->belongsTo(TipoTrabajador::class, 'id_tipo_trabajador', 'id_tipo_trabajador');
    }

    // 2. Función robusta para verificar roles
    public function tieneRol($roles)
    {
        // Si el usuario no tiene la relación por algún error, bloqueamos el acceso
        if (!$this->tipoTrabajador) {
            return false;
        }

        // Extraemos el rol de la BD, quitamos espacios y lo convertimos a MAYÚSCULAS
        $rolUsuario = strtoupper(trim($this->tipoTrabajador->tipo_trabajador));

        // Si estamos validando un arreglo de roles (Ej. ['Ingeniero', 'Secretaria'])
        if (is_array($roles)) {
            $rolesEnMayusculas = array_map('strtoupper', $roles);
            return in_array($rolUsuario, $rolesEnMayusculas);
        }

        // Si estamos validando un solo rol (Ej. 'Ingeniero')
        return $rolUsuario === strtoupper(trim($roles));
    }
}


