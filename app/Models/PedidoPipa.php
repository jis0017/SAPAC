<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PedidoPipa extends Model
{
    use HasFactory;
    
    protected $table = 'pedidos_pipas';
    protected $primaryKey = 'id_pedido';

    protected $fillable = [
        'toma_id',
        'id_trabajador', // <-- Añadido para permitir asignación masiva de choferes
        'pozo_id',
        'capacidad_litros',
        'solicitante',
        'domicilio_entrega',
        'telefono',
        'observaciones',
        'estado_entrega',
        'fecha_pedido',
        'fecha_entrega',
    ];

    public function toma()
    {
        return $this->belongsTo(TomasAgua::class, 'toma_id', 'id_toma');
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajadores::class, 'id_trabajador', 'id_trabajador');
    }

    public function pozo()
    {
        return $this->belongsTo(Pozos::class, 'pozo_id', 'id_pozo');
    }
}