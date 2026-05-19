<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grupo extends Model
{
    protected $table      = 'Grupo';
    protected $primaryKey = 'idGrupo';
    public    $timestamps = true;

    protected $fillable = [
        'nombre',
        'codigo',
        'paralelo',
        'turno',
        'hora_inicio',
        'hora_fin',
        'gestion',
        'cupos',
        'tipo',
        'estado',
    ];

    protected $casts = [
        'cupos'      => 'integer',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin'    => 'datetime:H:i',
    ];

    public function grupoMateriaDocentes(): HasMany
    {
        return $this->hasMany(GrupoMateriaDocente::class, 'idGrupo', 'idGrupo');
    }

    public function aulaGrupos(): HasMany
    {
        return $this->hasMany(AulaGrupo::class, 'idGrupo', 'idGrupo');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'idGrupo', 'idGrupo');
    }

    public function aulas(): BelongsToMany
    {
        return $this->belongsToMany(Aula::class, 'AulaGrupo', 'idGrupo', 'idAula')
            ->withTimestamps();
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'GrupoMateriaDocente', 'idGrupo', 'idDocente')
            ->withPivot('idMateria')
            ->withTimestamps();
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopePorTurno($query, string $turno)
    {
        return $query->where('turno', $turno);
    }
}