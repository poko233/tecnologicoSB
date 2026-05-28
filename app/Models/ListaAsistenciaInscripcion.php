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
        'idInscripcion',
        'idListaAsistencia',
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
}
