<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifaConfig extends Model
{
    use HasFactory;

    protected $table = 'tarifas_config';
    protected $primaryKey = 'id_tarifa';

    protected $fillable = [
        'id_tipo_predio',
        'limite_inferior',
        'limite_superior',
        'costo_metro_cubico',
        'costo_rango',
        'costo_acumulado',
        'consumo_minimo',
        'anio_vigencia'
    ];

    public function tipoPredio()
    {
        return $this->belongsTo(TipoPredio::class, 'id_tipo_predio', 'id_tipo_predio');
    }
}