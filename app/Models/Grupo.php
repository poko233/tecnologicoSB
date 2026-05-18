<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'Grupo';
    protected $primaryKey = 'idGrupo';

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';

    protected $fillable = [
        'nombre',
        'codigo',
        'paralelo',
        'turno',
        'horario',
        'gestion',
        'cupos',
        'tipo',
        'estado',
    ];
}