<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calculos extends Model
{
    use HasFactory;

    protected $table = 'lecturas_medidor';
    protected $primaryKey = 'id_lectura';

    // Agregamos todos los campos exactos de tu tabla en la base de datos
    protected $fillable = [
        'toma_id',
        'id_trabajador',
        'anio',
        'mes',
        'lectura',
        'consumo_mes',
        'consumo_acumulado',
        'es_excedente',
        'fecha_lectura'
    ];

    /**
     * Relación inversa: Un cálculo/lectura pertenece a una toma de agua
     */
    public function tomaAgua()
    {
        return $this->belongsTo(TomasAgua::class, 'toma_id', 'id_toma');
    }
}