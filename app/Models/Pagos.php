<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    use HasFactory;

    protected $table = 'pagos_historial';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'factura_id',
        'id_trabajador',
        'corte_id',
        'fecha_pago',
        'monto_pagado',
        'forma_pago',
        'no_transaccion',
        'estatus_pago',
        'motivo_cancelacion',
        'fecha_cancelacion'
    ];
}