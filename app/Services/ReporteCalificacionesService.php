<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReporteCalificacionesService
{
    /**
     * Obtener datos en formato HORIZONTAL (columnas = materias)
     */
    public function obtenerDatosHorizontal(
        ?int $idCarrera = null,
        ?string $gestion = null,
        ?string $turno = null
    ): array {
        // 1. Obtener la carrera
        $queryCarrera = DB::table('Carrera as c')
            ->select('c.idCarrera', 'c.nombreCarrera', 'c.codigo', 'c.regimen', 'c.duracion')
            ->where('c.estadoCarrera', 'activo');
        
        if ($idCarrera) {
            $queryCarrera->where('c.idCarrera', $idCarrera);
        }
        
        $carreras = $queryCarrera->get();
        $resultado = [];
        
        foreach ($carreras as $carrera) {
            // 2. Obtener materias de esta carrera con su código
            $materias = DB::table('CarreraMateria as cm')
                ->join('Materia as m', 'm.idMateria', '=', 'cm.idMateria')
                ->where('cm.idCarrera', $carrera->idCarrera)
                ->select('m.idMateria', 'm.nombreMateria', 'm.codigo')
                ->orderBy('m.codigo')
                ->get();
            
            if ($materias->isEmpty()) continue;
            
            // 3. Construir la consulta dinámica con PIVOT
            $selectFields = [
                'u.id as idUsuario',
                'u.ci as carnet',
                DB::raw("CONCAT(u.apellidoPaterno, ' ', IFNULL(u.apellidoMaterno,''), ' ', u.nombres) as nombreEstudiante")
            ];
            
            $caseFields = [];
            foreach ($materias as $mat) {
                $codigo = $mat->codigo ?: "M{$mat->idMateria}";
                $selectFields[] = DB::raw("MAX(CASE WHEN gmd.idMateria = {$mat->idMateria} THEN nf.nota_final END) as `{$codigo}`");
                $caseFields[] = "MAX(CASE WHEN gmd.idMateria = {$mat->idMateria} AND nf.estado = 'Reprobado' THEN 1 ELSE 0 END)";
            }
            
            // Estado general
            $reprobadoCheck = implode(' + ', $caseFields);
            $selectFields[] = DB::raw("CASE WHEN ({$reprobadoCheck}) > 0 THEN 'REPROBADO' ELSE 'APROBADO' END as estado");
            
            // 4. Query principal
            $query = DB::table('NotaFinal as nf')
                ->join('Inscripcion as i', 'i.idInscripcion', '=', 'nf.id_inscripcion')
                ->join('user as u', 'u.id', '=', 'i.idUsuario')
                ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupoMateriaDocente', '=', 'nf.id_grupo_materia_docente')
                ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
                ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'gmd.idMateria')
                ->where('cm.idCarrera', $carrera->idCarrera)
                ->select($selectFields)
                ->groupBy('u.id', 'u.ci', 'u.apellidoPaterno', 'u.apellidoMaterno', 'u.nombres')
                ->orderBy('u.apellidoPaterno')
                ->orderBy('u.nombres');
            
            // Filtrar por gestión
            if ($gestion) {
                $query->where('g.gestion', $gestion);
            }
            
            // Filtrar por turno
            if ($turno) {
                $query->where('g.turno', $turno);
            }
            
            $estudiantes = $query->get();
            
            if ($estudiantes->isEmpty()) continue;
            
            // 5. Armar resultado
            $resultado[] = [
                'carrera' => [
                    'idCarrera' => $carrera->idCarrera,
                    'nombre' => $carrera->nombreCarrera,
                    'codigo' => $carrera->codigo,
                    'regimen' => $carrera->regimen,
                    'duracion' => $carrera->duracion,
                ],
                'materias' => $materias->toArray(),
                'estudiantes' => $estudiantes->map(fn($e) => (array)$e)->toArray(),
                'gestion' => $gestion,
                'turno' => $turno,
            ];
        }
        
        return $resultado;
    }

    public function obtenerOpcionesFiltros(): array
    {
        // Carreras que tienen estudiantes con notas
        $carreras = DB::table('Carrera as c')
            ->join('CarreraMateria as cm', 'cm.idCarrera', '=', 'c.idCarrera')
            ->join('GrupoMateriaDocente as gmd', 'gmd.idMateria', '=', 'cm.idMateria')
            ->join('NotaFinal as nf', 'nf.id_grupo_materia_docente', '=', 'gmd.idGrupoMateriaDocente')
            ->select('c.idCarrera', 'c.nombreCarrera', 'c.codigo')
            ->where('c.estadoCarrera', 'activo')
            ->distinct()
            ->orderBy('c.nombreCarrera')
            ->get();

        // Gestiones disponibles
        $gestiones = DB::table('Grupo as g')
            ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'g.idGrupo')
            ->join('NotaFinal as nf', 'nf.id_grupo_materia_docente', '=', 'gmd.idGrupoMateriaDocente')
            ->select('g.gestion')
            ->distinct()
            ->orderBy('g.gestion')
            ->pluck('gestion');

        // Turnos disponibles
        $turnos = DB::table('Grupo as g')
            ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'g.idGrupo')
            ->join('NotaFinal as nf', 'nf.id_grupo_materia_docente', '=', 'gmd.idGrupoMateriaDocente')
            ->select('g.turno')
            ->whereNotNull('g.turno')
            ->distinct()
            ->orderBy('g.turno')
            ->pluck('g.turno');

        return [
            'carreras'  => $carreras,
            'gestiones' => $gestiones,
            'turnos'    => $turnos,
        ];
    }

}