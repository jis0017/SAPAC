<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TipoTrabajador extends Model
{
    use HasFactory;
    protected $table = 'tipo_trabajador';
    protected $primaryKey = 'id_tipo_trabajador';

    protected $fillable = [
        'tipo_trabajador',
    ];

    public function trabajadores()
    {
        return $this->hasMany(Trabajadores::class, 'id_tipo_trabajador', 'id_tipo_trabajador');
    }


}
