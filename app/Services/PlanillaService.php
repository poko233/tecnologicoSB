<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ElementoCompetencia;
use App\Models\GrupoMateriaDocente;
use App\Models\Inscripcion;
use App\Models\ListaAsistencia;
use App\Models\ListaAsistenciaInscripcion;
use App\Models\NotaElementoCompetencia;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlanillaService
{
    /**
     * Construye los datos completos de la planilla de calificación.
     *
     * @return array{grupo: array, materia: array, elementos: Collection, estudiantes: Collection}
     */
    public function obtenerPlanilla(int $idGrupoMateriaDocente, int $userId): array
    {
        $gmd = GrupoMateriaDocente::with(['grupo', 'materia'])
            ->findOrFail($idGrupoMateriaDocente);

        $this->verificarAcceso($gmd, $userId);

        $elementosActivos = ElementoCompetencia::activos()
            ->where('id_grupo_materia_docente', $idGrupoMateriaDocente)
            ->orderBy('id')
            ->get();

        $inscripciones = Inscripcion::with('usuario')
            ->where('idGrupo', $gmd->idGrupo)
            ->whereHas('usuario', fn($q) => $q->where('estado', 'ACTIVO'))
            ->get();

        $estudiantes = $this->formatearEstudiantes(
            $inscripciones,
            $elementosActivos,
            $gmd->idGrupoMateriaDocente
        );

        return [
            'grupo' => [
                'nombre' => $gmd->grupo->nombre,
                'gestion' => $gmd->grupo->gestion,
            ],
            'materia' => [
                'nombre' => $gmd->materia->nombreMateria,
            ],
            'elementos_competencia' => $elementosActivos->map(fn($ec) => [
                'id' => $ec->id,
                'nombre' => $ec->nombre,
            ]),
            'estudiantes' => $estudiantes,
        ];
    }

    /**
     * Formatea cada estudiante con sus notas de EC y su nota de asistencia.
     */
    private function formatearEstudiantes(Collection $inscripciones, Collection $elementosActivos, int $idGmd): Collection
    {
        if ($inscripciones->isEmpty()) {
            return collect();
        }

        $idsInscripcion = $inscripciones->pluck('idInscripcion');
        $idsEc = $elementosActivos->pluck('id');

        // Notas de EC existentes para estos estudiantes y ECs
        $notasEc = NotaElementoCompetencia::whereIn('id_inscripcion', $idsInscripcion)
            ->whereIn('id_elemento_competencia', $idsEc)
            ->get()
            ->groupBy('id_inscripcion');

        // Cálculo de nota de asistencia
        $asistenciasPorEstudiante = $this->calcularNotaAsistencia($idGmd, $idsInscripcion);

        return $inscripciones->map(function ($inscripcion) use ($notasEc, $elementosActivos, $asistenciasPorEstudiante) {
            $user = $inscripcion->usuario;
            $notas = $notasEc->get($inscripcion->idInscripcion, collect())->keyBy('id_elemento_competencia');

            $notasPorEc = $elementosActivos->map(function ($ec) use ($notas) {
                $nota = $notas->get($ec->id);
                return [
                    'id_elemento_competencia' => $ec->id,
                    'puntaje' => $nota ? (float) $nota->puntaje : null,
                ];
            });

            return [
                'id_inscripcion' => $inscripcion->idInscripcion,
                'id_usuario' => $user->id,
                'nombre_completo' => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
                'notas_ec' => $notasPorEc->values(),
                'nota_asistencia' => $asistenciasPorEstudiante[$inscripcion->idInscripcion] ?? 0.0,
            ];
        });
    }

    /**
     * Calcula la nota de asistencia (0-10) para cada estudiante según el enfoque A.
     *
     * @return array<int, float>  [idInscripcion => nota]
     */
    private function calcularNotaAsistencia(int $idGmd, Collection $idsInscripcion): array
    {
        // IDs de todas las ListaAsistencia de este GMD
        $listasIds = ListaAsistencia::where('id_grupo_materia_docente', $idGmd)
            ->pluck('idListaAsistencia');

        if ($listasIds->isEmpty()) {
            return $idsInscripcion->mapWithKeys(fn($id) => [$id => 0.0])->all();
        }

        // Total de sesiones distintas (fecha, idHorario) en todas las listas
        $totalSesiones = ListaAsistenciaInscripcion::whereIn('idListaAsistencia', $listasIds)
            ->selectRaw('COUNT(DISTINCT fecha, IFNULL(idHorario, 0)) as total')
            ->value('total');

        if ($totalSesiones == 0) {
            return $idsInscripcion->mapWithKeys(fn($id) => [$id => 0.0])->all();
        }

        // Obtener todos los registros de los estudiantes en esas listas
        $registros = ListaAsistenciaInscripcion::whereIn('idListaAsistencia', $listasIds)
            ->whereIn('idInscripcion', $idsInscripcion)
            ->get();

        // Agrupar por inscripción
        $porEstudiante = $registros->groupBy('idInscripcion');

        $pesos = ['Presente' => 1.0, 'Permiso' => 1.0, 'Atraso' => 0.5, 'Falta' => 0.0];

        $resultado = [];
        foreach ($idsInscripcion as $idInscripcion) {
            $registrosEstudiante = $porEstudiante->get($idInscripcion, collect());
            $sumaPesos = $registrosEstudiante->sum(fn($r) => $pesos[$r->tipo] ?? 0.0);
            $nota = ($sumaPesos / $totalSesiones) * 10;
            $resultado[$idInscripcion] = round(min($nota, 10.0), 2);
        }

        return $resultado;
    }

    private function verificarAcceso(GrupoMateriaDocente $gmd, int $userId): void
    {
        $user = request()->user();

        if ($user->roles()->pluck('rol')->contains('Administrador')) {
            return;
        }

        if ($gmd->idDocente !== $userId) {
            throw new HttpException(403, 'No tienes permiso para ver esta planilla.');
        }
    }
}