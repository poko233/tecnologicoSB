<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaAsistencia extends Model
{
    protected $table = 'ListaAsistencia';
    protected $primaryKey = 'idListaAsistencia';

    public $timestamps = true;

    protected $fillable = [
        'observacion',
        'fecha_inicio',
        'fecha_fin',
        'id_grupo_materia_docente',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
    ];

    public function grupoMateriaDocente()
    {
        return $this->belongsTo(
            GrupoMateriaDocente::class,
            'id_grupo_materia_docente',
            'idGrupoMateriaDocente'
        );
    }

    public function inscripciones()
    {
        return $this->hasMany(
            ListaAsistenciaInscripcion::class,
            'idListaAsistencia',
            'idListaAsistencia'
        );
    }
}
