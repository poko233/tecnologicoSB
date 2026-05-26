<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\CarreraUsuario;
use App\Models\Cuota;
use App\Models\Grupo;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscripcionAcademicaController extends Controller
{
    public function datosAcademicos()
    {
        $carreras = Carrera::orderBy('nombreCarrera')->get();

        $materias = DB::table('Materia as m')
            ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'm.idMateria')
            ->select(
                'm.*',
                'cm.idCarrera'
            )
            ->where('m.estado', 'activo')
            ->orderBy('cm.idCarrera')
            ->orderBy('m.semestre')
            ->orderBy('m.nombreMateria')
            ->get();

        $gruposBase = DB::table('GrupoMateriaDocente as gmd')
            ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
            ->join('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
            ->select(
                'g.idGrupo',
                'g.nombre as nombreGrupo',
                'g.nombre',
                'g.codigo',
                'g.paralelo',
                'g.turno',
                'g.gestion',
                'g.cupos as capacidad',
                'g.cupos',
                'g.tipo',
                'g.estado',
                'gmd.idMateria',
                'gmd.idDocente',
                'm.nombreMateria'
            )
            ->where('g.estado', 'activo')
            ->where('m.estado', 'activo')
            ->distinct()
            ->orderBy('gmd.idMateria')
            ->orderBy('g.idGrupo')
            ->get();

        $idsGrupos = $gruposBase->pluck('idGrupo')->unique()->values();

        $horarios = DB::table('GrupoHorario as gh')
            ->join('Horario as h', 'h.idHorario', '=', 'gh.idHorario')
            ->select(
                'gh.idGrupo',
                'h.idHorario',
                'h.dia',
                'h.horaInicio',
                'h.horaFin'
            )
            ->whereIn('gh.idGrupo', $idsGrupos)
            ->orderByRaw("
                CASE h.dia
                    WHEN 'Lunes' THEN 1
                    WHEN 'Martes' THEN 2
                    WHEN 'Miércoles' THEN 3
                    WHEN 'Miercoles' THEN 3
                    WHEN 'Jueves' THEN 4
                    WHEN 'Viernes' THEN 5
                    WHEN 'Sábado' THEN 6
                    WHEN 'Sabado' THEN 6
                    WHEN 'Domingo' THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('h.horaInicio')
            ->get()
            ->groupBy('idGrupo');

        $grupos = $gruposBase->map(function ($grupo) use ($horarios) {
            $grupo->horarios = $horarios->get($grupo->idGrupo, collect())->map(function ($horario) {
                return [
                    'idHorario' => $horario->idHorario,
                    'dia' => $horario->dia,
                    'horaInicio' => $horario->horaInicio,
                    'horaFin' => $horario->horaFin,
                ];
            })->values();

            return $grupo;
        });

        return response()->json([
            'carreras' => $carreras,
            'materias' => $materias,
            'grupos' => $grupos,
        ]);
    }

    public function inscribir(Request $request)
    {
        $validated = $request->validate([
            'idUsuario' => 'required|exists:user,id',
            'idCarrera' => 'required|exists:Carrera,idCarrera',
            'idGrupo' => 'required|exists:Grupo,idGrupo',
        ]);

        return DB::transaction(function () use ($validated) {
            $grupo = Grupo::where('idGrupo', $validated['idGrupo'])
                ->where('estado', 'activo')
                ->first();

            if (!$grupo) {
                return response()->json([
                    'message' => 'El grupo seleccionado está inactivo o no existe.',
                ], 422);
            }

            $existeGrupo = Inscripcion::where('idUsuario', $validated['idUsuario'])
                ->where('idGrupo', $validated['idGrupo'])
                ->exists();

            if ($existeGrupo) {
                return response()->json([
                    'message' => 'El estudiante ya está inscrito en este grupo',
                ], 422);
            }

            CarreraUsuario::firstOrCreate([
                'idUsuario' => $validated['idUsuario'],
                'idCarrera' => $validated['idCarrera'],
            ]);

            $inscripcion = Inscripcion::create([
                'idUsuario' => $validated['idUsuario'],
                'idGrupo' => $validated['idGrupo'],
            ]);

            $carrera = Carrera::findOrFail($validated['idCarrera']);

            if ($carrera->numeroCuotas && $carrera->cuotaMes) {
                for ($i = 1; $i <= $carrera->numeroCuotas; $i++) {
                    Cuota::create([
                        'monto' => $carrera->cuotaMes,
                        'numeroCuota' => $i,
                        'descuento' => 0,
                        'estadoCuota' => 'debe',
                        'idUsuario' => $validated['idUsuario'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Estudiante inscrito correctamente al grupo',
                'inscripcion' => $inscripcion,
            ], 201);
        });
    }
}