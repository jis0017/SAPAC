<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TomasAgua extends Model
{
    use HasFactory;

    protected $table = 'tomas_agua';
    protected $primaryKey = 'id_toma';

    protected $fillable = [
        'usuario_id',
        'pozo_id',
        'id_tipo_predio',
        'id_localidad',
        'no_medidor',
        'clave_catastral',
        'calle',
        'numero_exterior',
        'numero_interior',
        'municipio',
        'calculo_fijo',
        'aplica_descto2',
        'aplica_franquicia_anual',
        'volumen_franquicia_m3',
        'se_cobra_iva',
        'estatus'
    ];

    public function usuario()
    {
        return $this->belongsTo(usuarios::class, 'usuario_id', 'id_usuario');
    }

    public function tipoPredio()
    {
        return $this->belongsTo(TipoPredio::class, 'id_tipo_predio', 'id_tipo_predio');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidades::class, 'id_localidad', 'id_localidad');
    }

    public function calculos()
    {
        // Enlaza la toma con sus registros en la tabla de cálculos/lecturas
        return $this->hasMany(Calculos::class, 'toma_id', 'id_toma');
    }

    public function tarifaConfig()
    {
        // Relación con la tabla de tarifas según el tipo de predio
        return $this->belongsTo(TarifaConfig::class, 'id_tipo_predio', 'id_tipo_predio');
    }

    public function historialPagos()
    {
        return $this->hasMany(Facturas::class, 'toma_id', 'id_toma');
    }

    public function pozo()
    {
        return $this->belongsTo(Pozos::class, 'pozo_id', 'id_pozo');
    }

    public function cuotaPeriodo()
    {
        // Relación con la tabla de cuotas por período
        return $this->hasOne(CuotaPeriodo::class, 'id_tipo_predio', 'id_tipo_predio')
                    ->where('id_localidad', $this->id_localidad)
                    ->where('anio', date('Y')); 
    }
}