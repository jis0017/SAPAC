<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class usuarios extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'no_ciudadano',
        'razon_social',
        'apellidos',
        'rfc',
        'telefono'
    ];

    public function tomas()
    {
        return $this->hasMany(TomasAgua::class, 'usuario_id', 'id_usuario');
    }

  
}