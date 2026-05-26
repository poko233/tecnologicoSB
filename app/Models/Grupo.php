<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'Grupo';
    protected $primaryKey = 'idGrupo';

    protected $fillable = [
        'nombre',
        'codigo',
        'paralelo',
        'turno',
        'gestion',
        'cupos',
        'tipo',
        'estado',
    ];

    protected $casts = [
        'cupos' => 'integer',
    ];

    public function horarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Horario::class,
            'GrupoHorario',
            'idGrupo',
            'idHorario'
        )
            ->withPivot('idGrupoHorario')
            ->withTimestamps();
    }

    public function grupoMateriaDocentes(): HasMany
    {
        return $this->hasMany(GrupoMateriaDocente::class, 'idGrupo', 'idGrupo');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'idGrupo', 'idGrupo');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
}