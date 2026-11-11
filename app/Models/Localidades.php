<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localidades extends Model
{
    use HasFactory;

    // 1. Nombre exacto de la tabla en tu BD
    protected $table = 'localidades';

    // 2. Llave primaria personalizada
    protected $primaryKey = 'id_localidad';

    // 3. Campos que se pueden insertar masivamente
    protected $fillable = [
        'nombre_localidad'
    ];
}