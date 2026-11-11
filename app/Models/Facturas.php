<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facturas extends Model
{
    use HasFactory;

    protected $table = 'facturas';
    protected $primaryKey = 'id_factura';

    protected $fillable = [
        'toma_id',
        'id_tarifa',
        'no_factura',
        'fecha_lectura_anterior',
        'fecha_lectura_actual',
        'lectura_anterior',
        'lectura_actual',
        'consumo',
        'cuota_base_agua',
        'saneamiento',
        'infraestructura',
        'costo_medidor',
        'reconexion',
        'recargos',
        'rezagos',
        'sancion',
        'descuento_social',
        'descuento_pronto_pago',
        'bonificacion',
        'subtotal',
        'iva',
        'total_factura',
        'periodo_anio',
        'periodo_mes',
        'es_solo_contrato',
        'estatus_pago',
        'motivo_condonacion',
        'fecha_condonacion',
        'autorizado_por_trabajador_id'
    ];
}