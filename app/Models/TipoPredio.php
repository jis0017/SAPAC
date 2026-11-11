<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPredio extends Model
{
    use HasFactory;

    // 1. Nombre exacto de la tabla en tu base de datos
    protected $table = 'tipo_predio';

    // 2. Indicamos la llave primaria personalizada
    protected $primaryKey = 'id_tipo_predio';

    // 3. Campos que se pueden insertar/modificar
    protected $fillable = [
        'nombre_predio'
    ];
}