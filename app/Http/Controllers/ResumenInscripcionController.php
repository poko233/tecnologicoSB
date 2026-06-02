<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CarreraUsuario;
use App\Models\DocumentoEstudiante;
use App\Models\Inscripcion;
use App\Models\Cuota;
use Illuminate\Support\Facades\DB;

class ResumenInscripcionController extends Controller
{
    public function show($idUsuario)
    {
        $usuario = User::where('id', $idUsuario)->firstOrFail();

        $carreraUsuario = CarreraUsuario::with('carrera')
            ->where('idUsuario', $idUsuario)
            ->latest('idCarreraUsuario')
            ->first();

        $cuotas = collect();

        if ($carreraUsuario) {
            $cuotas = Cuota::where('idUsuario', $idUsuario)
                ->where('idCarrera', $carreraUsuario->idCarrera)
                ->orderByRaw("
                    CASE tipo
                        WHEN 'MATRICULA' THEN 1
                        WHEN 'MENSUAL' THEN 2
                        ELSE 3
                    END
                ")
                ->orderBy('numeroCuota')
                ->get();
        }

        $matricula = $cuotas
            ->where('tipo', 'MATRICULA')
            ->first();

        $cuotasMensuales = $cuotas
            ->where('tipo', 'MENSUAL')
            ->values();

        $totalMatricula = $matricula ? (float) $matricula->monto : 0;

        $totalCuotas = $cuotasMensuales
            ->where('estadoCuota', '!=', 'Condonado')
            ->sum('monto');

        $totalCondonado = $cuotasMensuales
            ->where('estadoCuota', 'Condonado')
            ->sum('monto');

        $totalPlan = $totalMatricula + $totalCuotas + $totalCondonado;

        $idsGrupos = Inscripcion::where('idUsuario', $idUsuario)
            ->pluck('idGrupo')
            ->filter()
            ->unique()
            ->values();

        $gruposBase = collect();

        if ($idsGrupos->count() > 0) {
            $gruposBase = DB::table('GrupoMateriaDocente as gmd')
                ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
                ->join('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
                ->whereIn('gmd.idGrupo', $idsGrupos)
                ->select(
                    'gmd.idGrupo',
                    'g.nombre',
                    'g.codigo',
                    'g.paralelo',
                    'g.turno',
                    'g.gestion',
                    'g.cupos',
                    'g.tipo',
                    'g.estado',
                    'gmd.idMateria',
                    'gmd.idDocente',
                    'm.nombreMateria',
                    'm.codigo as codigoMateria',
                    'm.semestre'
                )
                ->orderBy('gmd.idMateria')
                ->orderBy('gmd.idGrupo')
                ->get()
                ->unique('idGrupo')
                ->values();
        }

        $horarios = collect();

        if ($idsGrupos->count() > 0) {
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
        }

        $grupos = $gruposBase->map(function ($grupo) use ($horarios) {
            $grupo->horario = null;

            $grupo->horarios = $horarios
                ->get($grupo->idGrupo, collect())
                ->map(function ($horario) {
                    return [
                        'idHorario' => $horario->idHorario,
                        'dia' => $horario->dia,
                        'horaInicio' => $horario->horaInicio,
                        'horaFin' => $horario->horaFin,
                    ];
                })
                ->values();

            return $grupo;
        });

        $documentos = DocumentoEstudiante::where('idUsuario', $idUsuario)->get();

        return response()->json([
            'usuario' => [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidoPaterno' => $usuario->apellidoPaterno,
                'apellidoMaterno' => $usuario->apellidoMaterno,
                'ci' => $usuario->ci,
                'email' => $usuario->email,
                'celular' => $usuario->celular,
                'direccion' => $usuario->direccion,
            ],

            'carrera' => $carreraUsuario?->carrera,

            'grupos' => $grupos,

            'cuotas' => $cuotas,

            'planPago' => [
                'matricula' => $matricula,
                'cuotasMensuales' => $cuotasMensuales,
                'totalMatricula' => $totalMatricula,
                'totalCuotas' => $totalCuotas,
                'totalCondonado' => $totalCondonado,
                'totalPlan' => $totalPlan,
                'cantidadCuotas' => $cuotasMensuales->count(),
            ],

            'documentos' => $documentos,

            'validacion' => [
                'datosPersonales' => true,
                'datosAcademicos' => (bool) $carreraUsuario,
                'cuotasGeneradas' => $cuotas->count() > 0,
                'documentosCargados' => $documentos->count() > 0,
            ],
        ]);
    }

    public function finalizar($idUsuario)
    {
        $usuario = User::where('id', $idUsuario)->firstOrFail();

        return response()->json([
            'message' => 'Inscripción finalizada correctamente',
            'idUsuario' => $usuario->id,
        ]);
    }
}