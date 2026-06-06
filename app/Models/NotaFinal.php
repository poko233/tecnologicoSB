<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaFinal extends Model
{
    protected $table = 'NotaFinal';

    protected $fillable = [
        'id_inscripcion',
        'id_grupo_materia_docente',
        'nota_asistencia',
        'nota_academica',
        'nota_final',
        'estado',
        'segunda_instancia_nota',
        'observaciones',
        'calificado_por',
    ];

    protected $casts = [
        'nota_asistencia' => 'decimal:2',
        'nota_academica' => 'decimal:2',
        'nota_final' => 'decimal:2',
        'segunda_instancia_nota' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'id_inscripcion', 'idInscripcion');
    }

    public function grupoMateriaDocente(): BelongsTo
    {
        return $this->belongsTo(
            GrupoMateriaDocente::class,
            'id_grupo_materia_docente',
            'idGrupoMateriaDocente'
        );
    }

    public function calificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calificado_por', 'id');
    }
}