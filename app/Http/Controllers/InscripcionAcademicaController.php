<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        try {
            return DB::transaction(function () use ($validated) {
                $grupo = Grupo::where('idGrupo', $validated['idGrupo'])
                    ->where('estado', 'activo')
                    ->lockForUpdate()
                    ->first();

                if (!$grupo) {
                    throw ValidationException::withMessages([
                        'idGrupo' => [
                            'El grupo seleccionado está inactivo o no existe.',
                        ],
                    ]);
                }

                $existeGrupo = Inscripcion::where(
                    'idUsuario',
                    $validated['idUsuario']
                )
                    ->where('idGrupo', $validated['idGrupo'])
                    ->exists();

                if ($existeGrupo) {
                    throw ValidationException::withMessages([
                        'idGrupo' => [
                            'El estudiante ya está inscrito en este grupo.',
                        ],
                    ]);
                }

                if ((int) $grupo->cupos <= 0) {
                    throw ValidationException::withMessages([
                        'idGrupo' => [
                            'No hay cupos disponibles para este grupo.',
                        ],
                    ]);
                }

                /*
                 * Si el estudiante ya tiene carrera:
                 * - Permite continuar únicamente si es la misma carrera.
                 * - Rechaza una carrera diferente.
                 */
                $this->registrarCarreraUsuario(
                    (int) $validated['idUsuario'],
                    (int) $validated['idCarrera']
                );

                $inscripcion = Inscripcion::create([
                    'idUsuario' => $validated['idUsuario'],
                    'idGrupo' => $validated['idGrupo'],
                ]);

                $grupo->decrement('cupos');

                $grupoActualizado = Grupo::where(
                    'idGrupo',
                    $validated['idGrupo']
                )->first();

                return response()->json([
                    'message' => 'Estudiante inscrito correctamente al grupo.',
                    'inscripcion' => $inscripcion,
                    'grupo' => $grupoActualizado,
                    'cupos_restantes' => $grupoActualizado?->cupos,
                ], 201);
            });
        } catch (ValidationException $e) {
            return $this->respuestaValidacion($e);
        } catch (\Throwable $e) {
            \Log::error('ERROR INSCRIBIR ESTUDIANTE', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'No se pudo inscribir al estudiante.',
            ], 500);
        }
    }

    /**
     * Primera vez:
     * - Registra CarreraUsuario.
     * - Crea las cuotas.
     *
     * Siguientes veces:
     * - No crea otra carrera.
     * - Actualiza las cuotas de la carrera ya registrada.
     */
    public function guardarPagoCuotas(Request $request)
    {
        $validated = $request->validate([
            'idUsuario' => 'required|exists:user,id',
            'idCarrera' => 'required|exists:Carrera,idCarrera',

            'matricula' => 'required|array',
            'matricula.monto' => 'required|numeric|min:0',
            'matricula.descuento' => 'nullable|numeric|min:0',
            'matricula.fecha_vencimiento' => 'required|date',

            'cuotas' => 'required|array|min:1',
            'cuotas.*.numeroCuota' => 'required|integer|min:1|distinct',
            'cuotas.*.monto' => 'required|numeric|min:0',
            'cuotas.*.descuento' => 'nullable|numeric|min:0',
            'cuotas.*.fecha_vencimiento' => 'required|date',
            'cuotas.*.estadoCuota' => 'nullable|in:Debe,Condonado',
        ]);

        try {
            $resultado = DB::transaction(function () use ($validated) {
                $carreraCreada = $this->registrarCarreraUsuario(
                    (int) $validated['idUsuario'],
                    (int) $validated['idCarrera']
                );

                $resumenCuotas = $this->guardarOActualizarCuotas($validated);

                return [
                    'carreraCreada' => $carreraCreada,
                    ...$resumenCuotas,
                ];
            });

            return response()->json([
                'ok' => true,
                'guardado' => true,
                'actualizado' => !$resultado['carreraCreada'],
                'message' => $resultado['carreraCreada']
                    ? 'Carrera y plan de cuotas guardados correctamente.'
                    : 'Plan de cuotas actualizado correctamente.',
                'total_insertado' => $resultado['insertadas'],
                'total_actualizado' => $resultado['actualizadas'],
                'total_eliminado' => $resultado['eliminadas'],
            ], $resultado['carreraCreada'] ? 201 : 200);
        } catch (ValidationException $e) {
            return $this->respuestaValidacion($e);
        } catch (\Throwable $e) {
            \Log::error('ERROR GUARDAR O ACTUALIZAR CUOTAS', [
                'idUsuario' => $validated['idUsuario'] ?? null,
                'idCarrera' => $validated['idCarrera'] ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'guardado' => false,
                'message' => 'No se pudo guardar el plan de cuotas.',
            ], 500);
        }
    }

    /**
     * Crea la carrera solo si el estudiante no tiene una.
     *
     * Retorna:
     * - true: si se creó CarreraUsuario.
     * - false: si el estudiante ya tenía la misma carrera.
     *
     * Lanza error 422 si intenta registrar una carrera diferente.
     */
    private function registrarCarreraUsuario(
        int $idUsuario,
        int $idCarrera
    ): bool {
        $carreraActual = DB::table('CarreraUsuario')
            ->where('idUsuario', $idUsuario)
            ->lockForUpdate()
            ->first();

        if ($carreraActual) {
            if ((int) $carreraActual->idCarrera !== $idCarrera) {
                $nombreCarreraActual = DB::table('Carrera')
                    ->where('idCarrera', $carreraActual->idCarrera)
                    ->value('nombreCarrera');

                throw ValidationException::withMessages([
                    'idCarrera' => [
                        'El estudiante ya está inscrito en la carrera: ' .
                        ($nombreCarreraActual
                            ?: 'ID ' . $carreraActual->idCarrera) .
                        '. Solo se pueden editar sus cuotas; no se puede cambiar ni agregar otra carrera.',
                    ],
                ]);
            }

            return false;
        }

        $columnas = DB::getSchemaBuilder()
            ->getColumnListing('CarreraUsuario');

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

        return true;
    }

    /**
     * Actualiza las cuotas existentes por tipo y número.
     *
     * Ejemplos:
     * - MATRICULA:0
     * - MENSUAL:1
     * - MENSUAL:2
     *
     * No elimina cuotas que ya tienen fecha_pago.
     */
    private function guardarOActualizarCuotas(array $validated): array
    {
        $idUsuario = (int) $validated['idUsuario'];
        $idCarrera = (int) $validated['idCarrera'];
        $ahora = now();

        $filasEntrantes = [];

        $filasEntrantes['MATRICULA:0'] = [
            'idUsuario' => $idUsuario,
            'idCarrera' => $idCarrera,
            'tipo' => 'MATRICULA',
            'monto' => $validated['matricula']['monto'],
            'numeroCuota' => '0',
            'fecha_vencimiento' => $validated['matricula']['fecha_vencimiento'],
            'descuento' => $validated['matricula']['descuento'] ?? 0,
            'estadoCuota' => 'Debe',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];

        foreach ($validated['cuotas'] as $cuota) {
            $numeroCuota = (int) $cuota['numeroCuota'];

            $filasEntrantes['MENSUAL:' . $numeroCuota] = [
                'idUsuario' => $idUsuario,
                'idCarrera' => $idCarrera,
                'tipo' => 'MENSUAL',
                'monto' => $cuota['monto'],
                'numeroCuota' => (string) $numeroCuota,
                'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                'descuento' => $cuota['descuento'] ?? 0,
                'estadoCuota' => $cuota['estadoCuota'] ?? 'Debe',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        $existentes = DB::table('Cuota')
            ->where('idUsuario', $idUsuario)
            ->where('idCarrera', $idCarrera)
            ->lockForUpdate()
            ->get();

        $existentesPorClave = $existentes->keyBy(function ($cuota) {
            return strtoupper((string) $cuota->tipo) .
                ':' .
                (int) $cuota->numeroCuota;
        });

        $insertadas = 0;
        $actualizadas = 0;
        $eliminadas = 0;

        foreach ($filasEntrantes as $clave => $fila) {
            $existente = $existentesPorClave->get($clave);

            if (!$existente) {
                DB::table('Cuota')->insert($fila);
                $insertadas++;
                continue;
            }

            /*
             * fecha_pago no se modifica.
             * Si una cuota ya fue pagada, el pago sigue conservado.
             */
            DB::table('Cuota')
                ->where('idCuota', $existente->idCuota)
                ->update([
                    'monto' => $fila['monto'],
                    'fecha_vencimiento' => $fila['fecha_vencimiento'],
                    'descuento' => $fila['descuento'],
                    'estadoCuota' => $fila['estadoCuota'],
                    'updated_at' => $ahora,
                ]);

            $actualizadas++;
        }

        /*
         * Si se reduce la cantidad de cuotas, elimina solamente
         * las cuotas quitadas que todavía no tengan pago.
         */
        foreach ($existentes as $existente) {
            $clave = strtoupper((string) $existente->tipo) .
                ':' .
                (int) $existente->numeroCuota;

            if (array_key_exists($clave, $filasEntrantes)) {
                continue;
            }

            if (!empty($existente->fecha_pago)) {
                throw ValidationException::withMessages([
                    'cuotas' => [
                        "No se puede eliminar la cuota {$existente->numeroCuota} porque ya tiene un pago registrado.",
                    ],
                ]);
            }

            DB::table('Cuota')
                ->where('idCuota', $existente->idCuota)
                ->delete();

            $eliminadas++;
        }

        return [
            'insertadas' => $insertadas,
            'actualizadas' => $actualizadas,
            'eliminadas' => $eliminadas,
        ];
    }

    private function respuestaValidacion(ValidationException $e)
    {
        $errors = $e->errors();

        $mensaje = collect($errors)->flatten()->first()
            ?? 'Los datos enviados no son válidos.';

        return response()->json([
            'ok' => false,
            'guardado' => false,
            'message' => $mensaje,
            'errors' => $errors,
        ], 422);
    }
}