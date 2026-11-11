<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pozos extends Model
{
    use HasFactory;
    protected $table = 'ubicacion_pozos';
    protected $primaryKey = 'id_pozo';
    protected $fillable = [
        'nombre_pozo',
        'latitud',
        'longitud',
        'altitud',
        'estado'
        ];
        

}
