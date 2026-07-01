<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsignacionesController extends Controller
{
    private const ROL_ESTUDIANTE_ID = 2;
    private const SEMESTRE_INICIAL = 1;

    /**
     * Lista las carreras activas para que el usuario seleccione
     * antes de cargar la tabla de estudiantes.
     *
     * GET /api/asignaciones/carreras
     */
   public function carreras(): JsonResponse
{
    $carreras = DB::table('Carrera')
        ->where('estadoCarrera', 'activo')
        ->orderBy('nombreCarrera')
        ->get([
            'idCarrera',
            'nombreCarrera',
            'codigo',
            'regimen',
            'estadoCarrera',
        ]);

    return response()->json([
        'carreras' => $carreras,
    ]);
}

    /**
     * Lista solo los estudiantes de la carrera seleccionada.
     *
     * GET /api/asignaciones/estudiantes?idCarrera=1
     */
    public function estudiantes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idCarrera' => [
                'required',
                'integer',
                'exists:Carrera,idCarrera',
            ],
        ]);

        $idCarrera = (int) $validated['idCarrera'];

        $estudiantes = DB::table('user as u')
            ->leftJoin('Estudiante as est', 'est.id_usuario', '=', 'u.id')
            ->whereExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('user_rol as ur')
                    ->whereColumn('ur.id_user', 'u.id')
                    ->where('ur.id_rol', self::ROL_ESTUDIANTE_ID);
            })
            ->whereExists(function ($query) use ($idCarrera) {
                $query
                    ->selectRaw('1')
                    ->from('CarreraUsuario as cu')
                    ->whereColumn('cu.idUsuario', 'u.id')
                    ->where('cu.idCarrera', $idCarrera);
            })
            ->select(
                'u.id',
                'u.usuario',
                'u.ci',
                'u.nombres',
                'u.apellidoPaterno',
                'u.apellidoMaterno',
                'u.genero',
                'u.fecha_nac',
                'u.email',
                'u.telefono',
                'u.celular',
                'u.direccion',
                'u.expedido',
                'u.codigo_qr',
                'u.verificacion',
                'u.foto',
                'u.estado',
                'u.created_at',
                'u.updated_at',
                'est.matricula as matricula',
                DB::raw("DATE_FORMAT(u.created_at, '%d/%m/%Y') as fechaInscripcion")
            )
            ->selectRaw(
                '
                EXISTS (
                    SELECT 1
                    FROM Inscripcion as i
                    INNER JOIN GrupoMateriaDocente as gmd
                        ON gmd.idGrupo = i.idGrupo
                    INNER JOIN CarreraMateria as cm
                        ON cm.idMateria = gmd.idMateria
                    WHERE i.idUsuario = u.id
                        AND cm.idCarrera = ?
                ) as yaInscrito
                ',
                [$idCarrera]
            )
            ->orderBy('u.apellidoPaterno')
            ->orderBy('u.apellidoMaterno')
            ->orderBy('u.nombres')
            ->get()
            ->map(function ($estudiante) {
                $estudiante->id = (int) $estudiante->id;
                $estudiante->yaInscrito = (bool) $estudiante->yaInscrito;

                return $estudiante;
            })
            ->values();

        return response()->json([
            'estudiantes' => $estudiantes,
        ]);
    }

    /**
     * Muestra el detalle del estudiante, carreras asociadas,
     * documentos e inscripciones ya realizadas.
     *
     * GET /api/asignaciones/estudiantes/{idUsuario}
     */
  public function detalleEstudiante(int $idUsuario): JsonResponse
{
    $estudiante = User::query()
        ->where('id', $idUsuario)
        ->whereExists(function ($query) {
            $query
                ->selectRaw('1')
                ->from('user_rol as ur')
                ->whereColumn('ur.id_user', 'user.id')
                ->where('ur.id_rol', self::ROL_ESTUDIANTE_ID);
        })
        ->firstOrFail();

    $documentos = DB::table('DocumentoEstudiante')
        ->where('idUsuario', $idUsuario)
        ->select(
            'idDocumentoEstudiante',
            'nombreDocumento',
            'estadoDocumento',
            'idUsuario'
        )
        ->orderBy('nombreDocumento')
        ->get();

    $documentosRequeridos = [
        'Carnet de identidad',
        'Certificado de nacimiento',
        'Título de bachiller',
    ];

    $documentosPresentados = $documentos
        ->where('estadoDocumento', 1)
        ->pluck('nombreDocumento')
        ->map(fn ($nombre) => mb_strtolower(trim((string) $nombre)))
        ->toArray();

    $documentosPendientes = collect($documentosRequeridos)
        ->filter(function (string $documento) use ($documentosPresentados) {
            return !in_array(
                mb_strtolower($documento),
                $documentosPresentados,
                true
            );
        })
        ->values();

    $carreras = DB::table('CarreraUsuario as cu')
        ->join('Carrera as c', 'c.idCarrera', '=', 'cu.idCarrera')
        ->where('cu.idUsuario', $idUsuario)
        ->select(
            'c.idCarrera',
            'c.nombreCarrera',
            'c.codigo',
            'c.regimen',
            'c.estadoCarrera'
        )
        ->orderBy('c.nombreCarrera')
        ->get()
        ->map(function ($carrera) {
            return [
                'idCarrera' => (int) $carrera->idCarrera,
                'nombreCarrera' => $carrera->nombreCarrera,
                'codigo' => $carrera->codigo,
                'regimen' => $carrera->regimen,
                'estadoCarrera' => $carrera->estadoCarrera,
            ];
        })
        ->values();

    $inscripciones = DB::table('Inscripcion as i')
        ->join('Grupo as g', 'g.idGrupo', '=', 'i.idGrupo')
        ->leftJoin(
            'GrupoMateriaDocente as gmd',
            'gmd.idGrupo',
            '=',
            'g.idGrupo'
        )
        ->leftJoin('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
        ->leftJoin(
            'CarreraMateria as cm',
            'cm.idMateria',
            '=',
            'm.idMateria'
        )
        ->leftJoin('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
        ->where('i.idUsuario', $idUsuario)
        ->select(
            'i.idInscripcion',
            'i.idGrupo',
            'g.nombre as nombreGrupo',
            'g.codigo as codigoGrupo',
            'g.paralelo',
            'g.turno',
            'g.gestion',
            'm.idMateria',
            'm.nombreMateria',
            'm.codigo as codigoMateria',
            'm.semestre',
            'c.idCarrera',
            'c.nombreCarrera'
        )
        ->distinct()
        ->orderBy('m.semestre')
        ->orderBy('m.nombreMateria')
        ->get();

    return response()->json([
        'estudiante' => $estudiante,
        'carreras' => $carreras,
        'documentos' => $documentos,
        'documentosPendientes' => $documentosPendientes,
        'debeDocumentos' => $documentosPendientes->isNotEmpty(),
        'inscripciones' => $inscripciones,
    ]);
}

    /**
     * Devuelve materias de semestre 1 y sus grupos disponibles
     * para una carrera específica.
     *
     * GET /api/asignaciones/estudiantes/{idUsuario}/semestre-uno?idCarrera=1
     */
    public function materiasSemestreUno(
        Request $request,
        int $idUsuario
    ): JsonResponse {
        $request->validate([
            'idCarrera' => [
                'nullable',
                'integer',
                'exists:Carrera,idCarrera',
            ],
        ]);

        $this->obtenerEstudiante($idUsuario);

        $idCarrera = $this->resolverCarreraDelEstudiante(
            $idUsuario,
            $request->filled('idCarrera')
                ? (int) $request->query('idCarrera')
                : null
        );

        $materias = DB::table('Materia as m')
            ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'm.idMateria')
            ->join('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
            ->join(
                'GrupoMateriaDocente as gmd',
                'gmd.idMateria',
                '=',
                'm.idMateria'
            )
            ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
            ->where('cm.idCarrera', $idCarrera)
            ->where('m.semestre', self::SEMESTRE_INICIAL)
            ->where('m.estado', 'activo')
            ->where('g.estado', 'activo')
            ->where('g.cupos', '>', 0)
            ->select(
                'm.idMateria',
                'm.nombreMateria',
                'm.codigo as codigoMateria',
                'm.semestre',
                'c.idCarrera',
                'c.nombreCarrera',
                'g.idGrupo',
                'g.nombre as nombreGrupo',
                'g.codigo as codigoGrupo',
                'g.paralelo',
                'g.turno',
                'g.gestion',
                'g.cupos'
            )
            ->selectRaw(
                '
                EXISTS (
                    SELECT 1
                    FROM Inscripcion as i
                    INNER JOIN GrupoMateriaDocente as gmdInscrito
                        ON gmdInscrito.idGrupo = i.idGrupo
                    WHERE i.idUsuario = ?
                        AND gmdInscrito.idMateria = m.idMateria
                ) as yaInscrito
                ',
                [$idUsuario]
            )
            ->distinct()
            ->orderBy('m.nombreMateria')
            ->orderByRaw("FIELD(g.turno, 'Mañana', 'Tarde', 'Noche')")
            ->orderBy('g.idGrupo')
            ->get()
            ->groupBy('idMateria')
            ->map(function ($items) {
                $primero = $items->first();

                $gruposDisponibles = $items
                    ->unique('idGrupo')
                    ->map(function ($grupo) {
                        return [
                            'idGrupo' => (int) $grupo->idGrupo,
                            'nombreGrupo' => $grupo->nombreGrupo,
                            'codigoGrupo' => $grupo->codigoGrupo,
                            'paralelo' => $grupo->paralelo,
                            'turno' => $grupo->turno,
                            'gestion' => $grupo->gestion,
                            'cupos' => (int) $grupo->cupos,
                        ];
                    })
                    ->values();

                return [
                    'idMateria' => (int) $primero->idMateria,
                    'nombreMateria' => $primero->nombreMateria,
                    'codigoMateria' => $primero->codigoMateria,
                    'semestre' => (int) $primero->semestre,
                    'idCarrera' => (int) $primero->idCarrera,
                    'nombreCarrera' => $primero->nombreCarrera,
                    'yaInscrito' => (bool) $primero->yaInscrito,
                    'grupoSeleccionado' => null,
                    'gruposDisponibles' => $gruposDisponibles,
                ];
            })
            ->values();

        return response()->json([
            'materias' => $materias,
        ]);
    }

    /**
     * Inscribe al estudiante en las materias activas de semestre 1,
     * según la carrera y turno seleccionados.
     *
     * POST /api/asignaciones/estudiantes/{idUsuario}/inscribir-semestre-uno
     */
    public function inscribirSemestreUno(
        Request $request,
        int $idUsuario
    ): JsonResponse {
        $validated = $request->validate([
            'idCarrera' => [
                'required',
                'integer',
                'exists:Carrera,idCarrera',
            ],
            'turno' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        $idCarrera = (int) $validated['idCarrera'];
        $turno = $this->normalizarTurno($validated['turno']);

        if ($turno === null) {
            throw ValidationException::withMessages([
                'turno' => [
                    'El turno seleccionado no es válido. '
                    . 'Seleccione Mañana, Tarde o Noche.',
                ],
            ]);
        }

        $estudiante = $this->obtenerEstudiante($idUsuario);

        $this->verificarCarreraDelEstudiante($idUsuario, $idCarrera);

        return DB::transaction(function () use (
            $idUsuario,
            $idCarrera,
            $turno,
            $estudiante
        ) {
            $materiasConGrupos = DB::table('Materia as m')
                ->join(
                    'CarreraMateria as cm',
                    'cm.idMateria',
                    '=',
                    'm.idMateria'
                )
                ->join(
                    'GrupoMateriaDocente as gmd',
                    'gmd.idMateria',
                    '=',
                    'm.idMateria'
                )
                ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
                ->where('cm.idCarrera', $idCarrera)
                ->where('m.semestre', self::SEMESTRE_INICIAL)
                ->where('m.estado', 'activo')
                ->where('g.estado', 'activo')
                ->select(
                    'm.idMateria',
                    'm.nombreMateria',
                    'g.idGrupo',
                    'g.turno',
                    'g.cupos'
                )
                ->distinct()
                ->orderBy('m.nombreMateria')
                ->orderByRaw("FIELD(g.turno, 'Mañana', 'Tarde', 'Noche')")
                ->orderBy('g.idGrupo')
                ->get()
                ->groupBy('idMateria');

            if ($materiasConGrupos->isEmpty()) {
                return response()->json([
                    'message' => 'No existen materias activas de semestre 1 para esta carrera.',
                ], 422);
            }

            $inscritas = [];
            $omitidas = [];
            $sinCupos = [];
            $cuposDescontados = 0;

            foreach ($materiasConGrupos as $itemsGrupo) {
                $primero = $itemsGrupo->first();

                $yaInscritoEnMateria = DB::table('Inscripcion as i')
                    ->join(
                        'GrupoMateriaDocente as gmd',
                        'gmd.idGrupo',
                        '=',
                        'i.idGrupo'
                    )
                    ->where('i.idUsuario', $idUsuario)
                    ->where('gmd.idMateria', $primero->idMateria)
                    ->exists();

                if ($yaInscritoEnMateria) {
                    $omitidas[] = $primero->nombreMateria;
                    continue;
                }

                $grupoCandidato = $itemsGrupo
                    ->first(function ($grupo) use ($turno) {
                        return $this->normalizarTurno($grupo->turno) === $turno;
                    });

                if (!$grupoCandidato) {
                    $omitidas[] =
                        $primero->nombreMateria
                        . " (no existe grupo en turno {$turno})";
                    continue;
                }

                $grupo = DB::table('Grupo')
                    ->where('idGrupo', $grupoCandidato->idGrupo)
                    ->where('estado', 'activo')
                    ->lockForUpdate()
                    ->first();

                if (!$grupo) {
                    $omitidas[] =
                        $primero->nombreMateria
                        . ' (grupo inactivo o no disponible)';
                    continue;
                }

                /*
                 * Verifica nuevamente luego de bloquear el grupo.
                 * Así se evita duplicar inscripciones si dos solicitudes
                 * intentan procesar al mismo estudiante al mismo tiempo.
                 */
                $yaInscritoLuegoDelBloqueo = DB::table('Inscripcion as i')
                    ->join(
                        'GrupoMateriaDocente as gmd',
                        'gmd.idGrupo',
                        '=',
                        'i.idGrupo'
                    )
                    ->where('i.idUsuario', $idUsuario)
                    ->where('gmd.idMateria', $primero->idMateria)
                    ->exists();

                if ($yaInscritoLuegoDelBloqueo) {
                    $omitidas[] = $primero->nombreMateria;
                    continue;
                }

                if ((int) $grupo->cupos <= 0) {
                    $sinCupos[] = $primero->nombreMateria;
                    continue;
                }

                $descontado = DB::table('Grupo')
                    ->where('idGrupo', $grupo->idGrupo)
                    ->where('estado', 'activo')
                    ->where('cupos', '>', 0)
                    ->decrement('cupos');

                if ($descontado !== 1) {
                    $sinCupos[] = $primero->nombreMateria;
                    continue;
                }

                DB::table('Inscripcion')->insert([
                    'idUsuario' => $estudiante->id,
                    'idGrupo' => $grupo->idGrupo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $cuposDescontados++;

                $inscritas[] = $primero->nombreMateria;
            }

            if (count($inscritas) === 0) {
                return response()->json([
                    'message' => 'No se registraron materias nuevas para este estudiante.',
                    'inscritas' => $inscritas,
                    'omitidas' => $omitidas,
                    'sinCupos' => $sinCupos,
                    'cuposDescontados' => $cuposDescontados,
                ], 422);
            }

            $mensaje = count($sinCupos) > 0
                ? 'Inscripción realizada parcialmente. Algunas materias no tenían cupos disponibles.'
                : 'Inscripción automática realizada correctamente.';

            return response()->json([
                'message' => $mensaje,
                'inscritas' => $inscritas,
                'omitidas' => $omitidas,
                'sinCupos' => $sinCupos,
                'cuposDescontados' => $cuposDescontados,
            ]);
        });
    }

    /**
     * Actualiza los datos del estudiante desde el módulo Asignaciones.
     *
     * PUT /api/asignaciones/estudiantes/{idUsuario}
     */
    public function actualizarEstudiante(
        Request $request,
        int $idUsuario
    ): JsonResponse {
        $estudiante = $this->obtenerEstudiante($idUsuario);

        $validated = $request->validate([
            'apellidoPaterno' => ['required', 'string', 'max:50'],
            'apellidoMaterno' => ['nullable', 'string', 'max:50'],
            'nombres' => ['required', 'string', 'max:50'],
            'genero' => [
                'required',
                Rule::in([
                    'MASCULINO',
                    'FEMENINO',
                    'Masculino',
                    'Femenino',
                ]),
            ],
            'ci' => [
                'required',
                'string',
                'max:50',
                Rule::unique('user', 'ci')->ignore($estudiante->id),
            ],
            'expedido' => ['nullable', 'string', 'max:10'],
            'fecha_nac' => ['nullable', 'date'],
            'email' => [
                'nullable',
                'email',
                'max:50',
                Rule::unique('user', 'email')->ignore($estudiante->id),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:20'],
        ]);

        $validated['genero'] = mb_strtoupper($validated['genero']);

        $estudiante->update($validated);

        return response()->json([
            'message' => 'Estudiante actualizado correctamente.',
            'estudiante' => $estudiante->fresh(),
        ]);
    }

    /**
     * Obtiene al usuario únicamente si tiene rol de estudiante.
     */
    private function obtenerEstudiante(int $idUsuario): User
    {
        return User::query()
            ->where('id', $idUsuario)
            ->whereExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('user_rol as ur')
                    ->whereColumn('ur.id_user', 'user.id')
                    ->where('ur.id_rol', self::ROL_ESTUDIANTE_ID);
            })
            ->firstOrFail();
    }

    /**
     * Obtiene la carrera indicada, o la primera carrera asignada
     * al estudiante si no se recibió idCarrera.
     */
    private function resolverCarreraDelEstudiante(
        int $idUsuario,
        ?int $idCarrera
    ): int {
        if ($idCarrera !== null && $idCarrera > 0) {
            $this->verificarCarreraDelEstudiante($idUsuario, $idCarrera);

            return $idCarrera;
        }

        $carreraPrincipal = DB::table('CarreraUsuario')
            ->where('idUsuario', $idUsuario)
            ->orderBy('idCarrera')
            ->value('idCarrera');

        if (!$carreraPrincipal) {
            throw ValidationException::withMessages([
                'idCarrera' => [
                    'El estudiante no tiene una carrera asignada.',
                ],
            ]);
        }

        return (int) $carreraPrincipal;
    }

    /**
     * Evita que se consulten o inscriban materias en una carrera
     * que no corresponde al estudiante.
     */
    private function verificarCarreraDelEstudiante(
        int $idUsuario,
        int $idCarrera
    ): void {
        $perteneceALaCarrera = DB::table('CarreraUsuario')
            ->where('idUsuario', $idUsuario)
            ->where('idCarrera', $idCarrera)
            ->exists();

        if (!$perteneceALaCarrera) {
            throw ValidationException::withMessages([
                'idCarrera' => [
                    'La carrera seleccionada no está asignada a este estudiante.',
                ],
            ]);
        }
    }

    /**
     * Convierte variaciones del turno a un valor único.
     */
    private function normalizarTurno(?string $turno): ?string
    {
        $valor = mb_strtolower(trim((string) $turno));

        return match ($valor) {
            'mañana',
            'manana',
            'morning' => 'Mañana',

            'tarde',
            'afternoon' => 'Tarde',

            'noche',
            'night' => 'Noche',

            default => null,
        };
    }
}