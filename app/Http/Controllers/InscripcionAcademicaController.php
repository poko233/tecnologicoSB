<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
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
            ->select('m.*', 'cm.idCarrera')
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

            $this->registrarCarreraUsuario(
                $validated['idUsuario'],
                $validated['idCarrera']
            );

            $inscripcion = Inscripcion::create([
                'idUsuario' => $validated['idUsuario'],
                'idGrupo' => $validated['idGrupo'],
            ]);

            return response()->json([
                'message' => 'Estudiante inscrito correctamente al grupo',
                'inscripcion' => $inscripcion,
            ], 201);
        });
    }

    public function guardarPagoCuotas(Request $request)
    {
        try {
            \Log::info('INICIO GUARDAR CUOTAS', $request->all());

            $validated = $request->validate([
                'idUsuario' => 'required|exists:user,id',
                'idCarrera' => 'required|exists:Carrera,idCarrera',

                'matricula' => 'required|array',
                'matricula.monto' => 'required|numeric|min:0',
                'matricula.descuento' => 'nullable|numeric|min:0',
                'matricula.fecha_vencimiento' => 'required|date',

                'cuotas' => 'required|array|min:1',
                'cuotas.*.numeroCuota' => 'required|integer|min:1',
                'cuotas.*.monto' => 'required|numeric|min:0',
                'cuotas.*.descuento' => 'nullable|numeric|min:0',
                'cuotas.*.fecha_vencimiento' => 'required|date',
                'cuotas.*.estadoCuota' => 'nullable|in:Debe,Condonado',
            ]);

            \Log::info('VALIDACION OK CUOTAS');

            $this->registrarCarreraUsuario(
                $validated['idUsuario'],
                $validated['idCarrera']
            );

            \Log::info('CARRERA USUARIO OK');

            DB::table('Cuota')
                ->where('idUsuario', $validated['idUsuario'])
                ->where('idCarrera', $validated['idCarrera'])
                ->delete();

            \Log::info('CUOTAS ANTERIORES BORRADAS');

            $now = now();

            $filas = [];

            $filas[] = [
                'idUsuario' => $validated['idUsuario'],
                'idCarrera' => $validated['idCarrera'],
                'tipo' => 'MATRICULA',
                'monto' => $validated['matricula']['monto'],
                'numeroCuota' => '0',
                'fecha_vencimiento' => $validated['matricula']['fecha_vencimiento'],
                'descuento' => $validated['matricula']['descuento'] ?? 0,
                'estadoCuota' => 'Debe',
                'fecha_pago' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($validated['cuotas'] as $cuota) {
                $filas[] = [
                    'idUsuario' => $validated['idUsuario'],
                    'idCarrera' => $validated['idCarrera'],
                    'tipo' => 'MENSUAL',
                    'monto' => $cuota['monto'],
                    'numeroCuota' => (string) $cuota['numeroCuota'],
                    'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                    'descuento' => $cuota['descuento'] ?? 0,
                    'estadoCuota' => $cuota['estadoCuota'] ?? 'Debe',
                    'fecha_pago' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            \Log::info('FILAS ARMADAS', [
                'cantidad' => count($filas),
            ]);

            DB::table('Cuota')->insert($filas);

            \Log::info('CUOTAS INSERTADAS');

            return response()->json([
                'ok' => true,
                'guardado' => true,
                'message' => 'Plan de cuotas guardado correctamente.',
                'total_insertado' => count($filas),
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('ERROR GUARDAR CUOTAS', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'guardado' => false,
                'message' => 'No se pudo guardar el plan de cuotas.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function registrarCarreraUsuario(int $idUsuario, int $idCarrera): void
    {
        $existe = DB::table('CarreraUsuario')
            ->where('idUsuario', $idUsuario)
            ->where('idCarrera', $idCarrera)
            ->exists();

        if ($existe) {
            return;
        }

        $columnas = DB::getSchemaBuilder()->getColumnListing('CarreraUsuario');

        $data = [
            'idUsuario' => $idUsuario,
            'idCarrera' => $idCarrera,
        ];

        if (in_array('created_at', $columnas, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columnas, true)) {
            $data['updated_at'] = now();
        }

        if (in_array('create_at', $columnas, true)) {
            $data['create_at'] = now();
        }

        if (in_array('update_at', $columnas, true)) {
            $data['update_at'] = now();
        }

        DB::table('CarreraUsuario')->insert($data);
    }
}