<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoHorario extends Model
{
    protected $table = 'grupo_horario';
    protected $primaryKey = 'idGrupoHorario';

    protected $fillable = [
        'idGrupo',
        'idHorario',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'idGrupo', 'idGrupo');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'idHorario', 'idHorario');
    }
}