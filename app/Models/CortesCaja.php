<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CortesCaja extends Model
{
    use HasFactory;

    protected $table = 'cortes_caja';
    protected $primaryKey = 'id_corte';

    protected $fillable = [
        'id_trabajador',
        'fecha_apertura',
        'fecha_cierre',
        'monto_inicial',
        'total_cobrado_sistema',
        'total_efectivo_entregado',
        'diferencia',
        'estatus',
        'observaciones',
    ];

    protected $casts = [
        'fecha_apertura'           => 'datetime',
        'fecha_cierre'             => 'datetime',
        'monto_inicial'            => 'decimal:2',
        'total_cobrado_sistema'    => 'decimal:2',
        'total_efectivo_entregado' => 'decimal:2',
        'diferencia'               => 'decimal:2',
    ];
}