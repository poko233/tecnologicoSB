<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GrupoMateriaDocente;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;

/**
 * Agrupa la lógica de consulta de Grupos-Materia-Docente.
 */
class GrupoMateriaDocenteService
{
    /**
     * Devuelve los grupos activos asignados a un docente,
     * incluyendo el número de estudiantes inscritos en cada grupo.
     *
     * @param int $userId ID del usuario (docente).
     * @return Collection
     */
    public function getGruposDelDocente(int $userId): Collection
    {
        $grupos = GrupoMateriaDocente::with([
            'grupo' => fn($q) => $q->where('estado', 'activo'),
            'materia',
        ])
            ->where('idDocente', $userId)
            ->whereHas('grupo', fn($q) => $q->where('estado', 'activo'))
            ->orderBy('created_at', 'desc')
            ->get();

        $inscripcionesCounts = Inscripcion::selectRaw('idGrupo, count(*) as total')
            ->whereIn('idGrupo', $grupos->pluck('idGrupo')->unique())
            ->groupBy('idGrupo')
            ->pluck('total', 'idGrupo');

        return $grupos->map(function ($gmd) use ($inscripcionesCounts) {
            return [
                'id_grupo_materia_docente' => $gmd->idGrupoMateriaDocente,
                'grupo' => $gmd->grupo->nombre,
                'paralelo' => $gmd->grupo->paralelo,
                'turno' => $gmd->grupo->turno,
                'gestion' => $gmd->grupo->gestion,
                'materia' => $gmd->materia->nombreMateria,
                'inscritos' => $inscripcionesCounts->get($gmd->idGrupo, 0),
            ];
        });
    }
}