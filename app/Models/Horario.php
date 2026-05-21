<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'Horario';
    protected $primaryKey = 'idHorario';

    protected $fillable = [
        'horaInicio',
        'horaFin',
        'dia',
    ];

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'GrupoHorario', 'idHorario', 'idGrupo')
            ->withTimestamps();
    }
}
