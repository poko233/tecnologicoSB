<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaAsistenciaInscripcion extends Model
{
    protected $table = 'ListaAsistenciaInscripcion';
    protected $primaryKey = 'idListaAsistenciaInscripcion';

    public $timestamps = true;

    protected $fillable = [
        'observacion',
        'tipo',
        'fecha',
        'idHorario',
        'idInscripcion',
        'idListaAsistencia',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(
            Inscripcion::class,
            'idInscripcion',
            'idInscripcion'
        );
    }

    public function listaAsistencia()
    {
        return $this->belongsTo(
            ListaAsistencia::class,
            'idListaAsistencia',
            'idListaAsistencia'
        );
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'idHorario', 'idHorario');
    }
}