<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReporteCalificacionesService
{
    public function obtenerDatos(?int $idCarrera = null, ?string $gestion = null): array
    {
        $queryCarreras = DB::table('Carrera as c')
            ->select('c.idCarrera', 'c.nombreCarrera', 'c.codigo',
                     'c.regimen', 'c.tipo', 'c.duracion')
            ->where('c.estadoCarrera', 'activo');

        if ($idCarrera) {
            $queryCarreras->where('c.idCarrera', $idCarrera);
        }

        $carreras = $queryCarreras->get();
        $resultado = [];

        foreach ($carreras as $carrera) {
            // LEFT JOIN para no perder grupos sin CarreraMateria
            $gmds = DB::table('GrupoMateriaDocente as gmd')
                ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
                ->join('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
                ->join('Docente as d', 'd.idDocente', '=', 'gmd.idDocente')
                ->join('user as u', 'u.id', '=', 'd.idDocente')
                ->leftJoin('CarreraMateria as cm', function ($join) use ($carrera) {
                    $join->on('cm.idMateria', '=', 'gmd.idMateria')
                         ->where('cm.idCarrera', '=', $carrera->idCarrera);
                })
                ->select(
                    'gmd.idGrupoMateriaDocente',
                    'g.idGrupo',
                    'g.nombre',
                    'g.gestion',
                    'm.idMateria',
                    'm.nombreMateria',
                    'd.idDocente',
                    DB::raw("CONCAT(u.nombres, ' ', IFNULL(u.apellidoPaterno,'')) as nombreDocente"),
                    'd.abreviaturaProfesional',
                    'cm.idCarrera as carrera_asignada'
                )
                ->distinct()
                ->get();

            // Filtrar: solo grupos con materia asignada a esta carrera
            $gmds = $gmds->filter(function($gmd) {
                return !is_null($gmd->carrera_asignada);
            });

            // Filtrar por gestión
            if ($gestion) {
                $gmds = $gmds->filter(function($gmd) use ($gestion) {
                    return $gmd->gestion === $gestion;
                });
            }

            $grupos = [];

            foreach ($gmds as $gmd) {
                $horario = DB::table('GrupoHorario as gh')
                    ->join('Horario as h', 'h.idHorario', '=', 'gh.idHorario')
                    ->where('gh.idGrupo', $gmd->idGrupo)
                    ->select('h.dia', 'h.horaInicio', 'h.horaFin')
                    ->get()
                    ->map(fn($h) => "{$h->dia} {$h->horaInicio}-{$h->horaFin}")
                    ->implode(', ');

                $estudiantes = DB::table('Inscripcion as i')
                    ->join('user as u', 'u.id', '=', 'i.idUsuario')
                    ->join('NotaFinal as nf', 'nf.id_inscripcion', '=', 'i.idInscripcion')
                    ->where('nf.id_grupo_materia_docente', $gmd->idGrupoMateriaDocente)
                    ->select(
                        'u.id as idUsuario',
                        'i.idInscripcion',
                        'u.ci as carnet',
                        DB::raw("CONCAT(u.nombres, ' ', IFNULL(u.apellidoPaterno,''), ' ', IFNULL(u.apellidoMaterno,'')) as nombreEstudiante"),
                        'nf.nota_asistencia',
                        'nf.nota_academica',
                        'nf.nota_final',
                        'nf.segunda_instancia_nota',
                        'nf.estado',
                        'nf.observaciones'
                    )
                    ->orderBy('u.nombres')
                    ->get();

                if ($estudiantes->isEmpty()) {
                    continue; // Saltar grupos sin estudiantes
                }

                $grupos[] = [
                    'idGrupoMateriaDocente' => $gmd->idGrupoMateriaDocente,
                    'grupo'                => $gmd->nombre,
                    'materia'              => $gmd->nombreMateria,
                    'docente'              => trim("{$gmd->abreviaturaProfesional} {$gmd->nombreDocente}"),
                    'horario'              => $horario ?: 'Sin horario asignado',
                    'estudiantes' => $estudiantes->map(fn($e) => (array) $e)->toArray(),
                ];
            }

            if (!empty($grupos)) {
                $resultado[] = [
                    'idCarrera' => $carrera->idCarrera,
                    'nombre'    => $carrera->nombreCarrera,
                    'codigo'    => $carrera->codigo,
                    'regimen'   => $carrera->regimen,
                    'tipo'      => $carrera->tipo,
                    'duracion'  => $carrera->duracion,
                    'grupos'    => $grupos,
                ];
            }
        }

        return $resultado;
    }

    public function estadisticasGrupo(array $estudiantes): array
    {
        $notas     = collect($estudiantes)->pluck('nota_final')->filter();
        $aprobados = collect($estudiantes)->filter(fn($e) => strtolower($e['estado']) === 'aprobado')->count();
        $total     = count($estudiantes);

        return [
            'total'      => $total,
            'aprobados'  => $aprobados,
            'reprobados' => $total - $aprobados,
            'promedio'   => $notas->avg() ? round($notas->avg(), 2) : 0,
            'nota_max'   => $notas->max() ?? 0,
            'nota_min'   => $notas->min() ?? 0,
        ];
    }
}