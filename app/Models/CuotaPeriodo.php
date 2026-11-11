<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuotaPeriodo extends Model
{
    use HasFactory;

    protected $table = 'cuotas_periodo';
    protected $primaryKey = 'id_cuota_periodo';

    protected $fillable = [
        'anio',
        'id_tipo_predio',
        'id_localidad',
        'cuota_mensual',
        'saneamiento_mensual',
        'otros_usos_mensual',
        'total_anual_sin_descuento',
        'porcentaje_descto1',
        'total_anual_descto1',
        'porcentaje_descto2',
        'total_anual_descto2',
        'costo_nuevo_contrato',
        'costo_reconexion'
    ];

    public function tipoPredio()
    {
        return $this->belongsTo(TipoPredio::class, 'id_tipo_predio', 'id_tipo_predio');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidades::class, 'id_localidad', 'id_localidad');
    }

    public function tomasAgua()
    {
        return $this->hasMany(TomasAgua::class, 'id_tipo_predio', 'id_tipo_predio')
                    ->where('id_localidad', $this->id_localidad);
    }
}