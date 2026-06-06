<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaElementoCompetencia extends Model
{
    /**
     * Nombre real de la tabla en la base de datos.
     */
    protected $table = 'NotaElementoCompetencia';

    /**
     * Campos asignables de forma masiva.
     */
    protected $fillable = [
        'id_elemento_competencia',
        'id_inscripcion',
        'puntaje',
        'observaciones',
    ];

    /**
     * Atributos con conversión de tipos.
     */
    protected $casts = [
        'puntaje' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Elemento de competencia al que pertenece esta nota.
     */
    public function elementoCompetencia(): BelongsTo
    {
        return $this->belongsTo(
            ElementoCompetencia::class,
            'id_elemento_competencia',
            'id'
        );
    }

    /**
     * Inscripción del estudiante evaluado.
     */
    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(
            Inscripcion::class,
            'id_inscripcion',
            'idInscripcion'
        );
    }
}