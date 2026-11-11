<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoTrabajador;
use App\Models\Trabajadores;
use Illuminate\Support\Facades\Hash;

class RolesYAdminSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear los roles principales
        $ingeniero = TipoTrabajador::firstOrCreate(['tipo_trabajador' => 'Ingeniero']);
        TipoTrabajador::firstOrCreate(['tipo_trabajador' => 'Secretaria']);
        TipoTrabajador::firstOrCreate(['tipo_trabajador' => 'Pipero']);

        // 2. Crear el usuario Maestro / Ingeniero encriptado de forma segura
        Trabajadores::firstOrCreate(
            ['usuario_trabajador' => 'admin_sapac'], // Evitar duplicados
            [
                'id_tipo_trabajador' => $ingeniero->id_tipo_trabajador,
                'nombre' => 'Ingeniero Administrador',
                'password' => Hash::make('ACATIC01'), // Hash seguro de Bcrypt
                'activo' => 1
            ]
        );
    }
}