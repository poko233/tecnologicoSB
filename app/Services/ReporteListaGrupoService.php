<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReporteListaGrupoService
{
    public function obtenerListaPorGrupo(?int $idGrupoMateriaDocente = null): array
    {
        $query = DB::table('Inscripcion as i')
            ->join('user as u', 'u.id', '=', 'i.idUsuario')
            ->join('Grupo as g', 'g.idGrupo', '=', 'i.idGrupo')
            ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'g.idGrupo')
            ->join('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
            ->join('Docente as d', 'd.idDocente', '=', 'gmd.idDocente')
            ->join('user as ud', 'ud.id', '=', 'd.idDocente')
            ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'gmd.idMateria')
            ->join('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
            ->select(
                'gmd.idGrupoMateriaDocente',
                'c.nombreCarrera as carrera',
                'g.nombre as grupo',
                'g.gestion',
                'g.turno',
                'm.nombreMateria as materia',
                DB::raw("CONCAT(ud.nombres, ' ', ud.apellidoPaterno) as docente"),
                'u.ci as carnet',
                DB::raw("CONCAT(u.apellidoPaterno, ' ', u.apellidoMaterno, ' ', u.nombres) as estudiante"),
                'u.celular',
                DB::raw("'' as observacion")
            )
            ->orderBy('u.apellidoPaterno');

        if ($idGrupoMateriaDocente) {
            $query->where('gmd.idGrupoMateriaDocente', $idGrupoMateriaDocente);
        }

        $inscritos = $query->get();

        if ($inscritos->isEmpty()) {
            return [];
        }

        $primero = $inscritos->first();
        
        return [[
            'grupo' => [
                'idGrupoMateriaDocente' => $primero->idGrupoMateriaDocente,
                'carrera' => $primero->carrera,
                'grupo' => $primero->grupo,
                'gestion' => $primero->gestion,
                'turno' => $primero->turno,
                'materia' => $primero->materia,
                'docente' => $primero->docente,
            ],
            'estudiantes' => $inscritos->map(fn($e) => (array)$e)->toArray(),
        ]];
    }

    public function obtenerFiltros(): array
    {
        $grupos = DB::table('GrupoMateriaDocente as gmd')
            ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
            ->join('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
            ->join('Docente as d', 'd.idDocente', '=', 'gmd.idDocente')
            ->join('user as u', 'u.id', '=', 'd.idDocente')
            ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'gmd.idMateria')
            ->join('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
            ->select(
                'gmd.idGrupoMateriaDocente',
                'g.nombre as grupo',
                'm.nombreMateria as materia',
                DB::raw("CONCAT(u.nombres, ' ', u.apellidoPaterno) as docente"),
                'c.nombreCarrera as carrera'
            )
            ->orderBy('g.nombre')
            ->get();

        return ['grupos' => $grupos];
    }
}