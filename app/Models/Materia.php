<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materia extends Model
{
    use HasFactory;

    protected $table      = 'Materia';
    protected $primaryKey = 'idMateria';
    public    $timestamps = true;

    protected $fillable = [
        'nombreMateria',
        'codigo',
        'semestre',
        'estado',
        'idPrerequisito',
    ];

    protected $casts = [
        'semestre'       => 'integer',
        'idPrerequisito' => 'integer',
    ];

    public function prerequisito(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'idPrerequisito', 'idMateria');
    }

    public function materiasConsecutivas(): HasMany
    {
        return $this->hasMany(Materia::class, 'idPrerequisito', 'idMateria');
    }

    public function carreraMaterias(): HasMany
    {
        return $this->hasMany(CarreraMateria::class, 'idMateria', 'idMateria');
    }

    public function carreras(): BelongsToMany
    {
        return $this->belongsToMany(Carrera::class, 'CarreraMateria', 'idMateria', 'idCarrera')
            ->withTimestamps();
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where('nombreMateria', 'like', "%{$termino}%")
            ->orWhere('codigo', 'like', "%{$termino}%");
    }
    public function asignacionesDocentes(): HasMany
{
    return $this->hasMany(
        GrupoMateriaDocente::class,
        'idMateria',
        'idMateria'
    );
}
}