<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoMateriaDocente extends Model
{
    protected $table = 'GrupoMateriaDocente';
    protected $primaryKey = 'idGrupoMateriaDocente';

    protected $fillable = [
        'idGrupo',
        'idMateria',
        'idDocente',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'idMateria', 'idMateria');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'idDocente', 'idDocente');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'idGrupo', 'idGrupo');
    }
}