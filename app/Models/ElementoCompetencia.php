<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ElementoCompetencia extends Model
{
    /**
     * Nombre real de la tabla en la base de datos.
     */
    protected $table = 'ElementoCompetencia';

    /**
     * Campos asignables de forma masiva.
     */
    protected $fillable = [
        'id_grupo_materia_docente',
        'nombre',
        'observaciones',
        'estado',
    ];

    /**
     * Atributos con conversión de tipos.
     */
    protected $casts = [
        'estado' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Grupo‑Materia‑Docente al que pertenece este elemento.
     */
    public function grupoMateriaDocente(): BelongsTo
    {
        return $this->belongsTo(
            GrupoMateriaDocente::class,
            'id_grupo_materia_docente',
            'idGrupoMateriaDocente'
        );
    }

    /**
     * Notas registradas para este elemento de competencia.
     */
    public function notasElementoCompetencia(): HasMany
    {
        return $this->hasMany(
            NotaElementoCompetencia::class,
            'id_elemento_competencia',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Solo elementos con estado "activo".
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Solo elementos con estado "inactivo".
     */
    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('estado', 'inactivo');
    }
}