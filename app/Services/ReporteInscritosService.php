<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReporteInscritosService
{
    public function obtenerInscritosPorCarrera(
        ?int $idCarrera = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $query = DB::table('Inscripcion as i')
            ->join('user as u', 'u.id', '=', 'i.idUsuario')
            ->join('Grupo as g', 'g.idGrupo', '=', 'i.idGrupo')
            ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'g.idGrupo')
            ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'gmd.idMateria')
            ->join('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
            ->select(
                'c.idCarrera',
                'c.nombreCarrera',
                'c.codigo as codigoCarrera',
                'u.ci as carnet',
                DB::raw("CONCAT(u.apellidoPaterno, ' ', u.apellidoMaterno, ' ', u.nombres) as estudiante"),
                'i.created_at as fecha_inscripcion',
                'g.gestion',
                'g.turno'
            )
            ->orderBy('c.nombreCarrera')
            ->orderBy('u.apellidoPaterno');

        // Filtro por carrera
        if ($idCarrera) {
            $query->where('c.idCarrera', $idCarrera);
        }

        // Filtro por fecha inicio
        if ($fechaInicio) {
            $query->whereDate('i.created_at', '>=', $fechaInicio);
        }

        // Filtro por fecha fin
        if ($fechaFin) {
            $query->whereDate('i.created_at', '<=', $fechaFin);
        }

        $inscritos = $query->get();

        // Agrupar por carrera
        $resultado = [];
        foreach ($inscritos as $ins) {
            $key = $ins->idCarrera;
            if (!isset($resultado[$key])) {
                $resultado[$key] = [
                    'carrera' => [
                        'idCarrera' => $ins->idCarrera,
                        'nombre'    => $ins->nombreCarrera,
                        'codigo'    => $ins->codigoCarrera,
                    ],
                    'estudiantes' => [],
                ];
            }
            $resultado[$key]['estudiantes'][] = (array) $ins;
        }

        return array_values($resultado);
    }
}