<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GrupoMateriaDocente;
use App\Models\Inscripcion;
use App\Models\ListaAsistencia;
use App\Models\ListaAsistenciaInscripcion;
use App\Models\Carrera;
use App\Http\Requests\RegistrarAsistenciaRequest;
use App\Http\Requests\BatchAsistenciaRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\AsistenciaExport;


class DocenteAsistenciaController extends Controller
{
    /**
     * GET /api/docente/grupos-asignados
     * Retorna los grupos activos asignados al docente (o todos si es administrador).
     */
    public function gruposAsignados(Request $request): JsonResponse
    {
        $user = $request->user();
        $esAdmin = $user->hasRole('Administrador');

        $query = GrupoMateriaDocente::with([
            'grupo' => fn($q) => $q->where('estado', 'activo'),
            'materia.carreras' => fn($q) => $q->where('estadoCarrera', 'activo'),
        ])
            ->whereHas('grupo', fn($q) => $q->where('estado', 'activo'));

        if (!$esAdmin) {
            $query->where('idDocente', $user->id);
        }

        $grupos = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($gmd) {
                $carrera = $gmd->materia->carreras->first();
                return [
                    'id_grupo_materia_docente' => $gmd->idGrupoMateriaDocente,
                    'grupo' => $gmd->grupo->nombre,
                    'turno' => $gmd->grupo->turno,
                    'materia' => $gmd->materia->nombreMateria,
                    'carrera' => $carrera?->nombreCarrera,
                    'regimen' => $carrera?->regimen,
                ];
            });

        return response()->json(['data' => $grupos]);
    }

    /**
     * GET /api/docente/grupos-asignados/{id}/estudiantes
     * Devuelve los estudiantes del grupo, la ListaAsistencia del período y
     * la asistencia de hoy desglosada por horario (o general).
     */
    public function estudiantes(int $idGrupoMateriaDocente, Request $request): JsonResponse
    {
        $user = $request->user();
        $gmd = GrupoMateriaDocente::with(['grupo.horarios', 'materia.carreras'])->findOrFail($idGrupoMateriaDocente);

        $esAdmin = $user->hasRole('Administrador');
        if (!$esAdmin && $gmd->idDocente !== $user->id) {
            return response()->json(['message' => 'No estás asignado a este grupo.'], 403);
        }

        $carrera = $gmd->materia->carreras()->where('estadoCarrera', 'activo')->first();
        if (!$carrera) {
            return response()->json(['message' => 'No se encontró una carrera activa asociada a esta materia.'], 400);
        }

        $lista = $this->obtenerOCrearListaAsistencia($gmd, $carrera);

        $horarios = $gmd->grupo->horarios;

        // Estudiantes activos inscritos
        $inscripciones = Inscripcion::with(['usuario.carreras'])
            ->where('idGrupo', $gmd->idGrupo)
            ->whereHas('usuario', fn($q) => $q->where('estado', 'ACTIVO'))
            ->get();

        // Si no hay estudiantes inscritos, devolver lista vacía con mensaje
        if ($inscripciones->isEmpty()) {
            return response()->json([
                'data' => [
                    'lista_asistencia' => [
                        'id' => $lista->idListaAsistencia,
                        'fecha_inicio' => $lista->fecha_inicio,
                        'fecha_fin' => $lista->fecha_fin,
                    ],
                    'horarios' => $horarios->map(fn($h) => [
                        'idHorario' => $h->idHorario,
                        'horaInicio' => $h->horaInicio,
                        'horaFin' => $h->horaFin,
                        'dia' => $h->dia,
                    ]),
                    'estudiantes' => [],
                ],
                'message' => 'No hay estudiantes inscritos en este grupo.',
            ]);
        }

        $hoy = Carbon::now()->toDateString();

        $estudiantes = $inscripciones->map(function ($inscripcion) use ($lista, $hoy, $horarios) {
            $user = $inscripcion->usuario;
            $carreraEstudiante = $user->carreras()->where('estadoCarrera', 'activo')->first();

            // Asistencias de hoy para este estudiante, agrupadas por idHorario (o null)
            $asistenciasHoy = ListaAsistenciaInscripcion::where('idInscripcion', $inscripcion->idInscripcion)
                ->where('idListaAsistencia', $lista->idListaAsistencia)
                ->where('fecha', $hoy)
                ->get();

            // Convertir a un mapa por idHorario (null representa sin horario)
            $asistenciaMap = $asistenciasHoy->mapWithKeys(function ($item) {
                return [($item->idHorario ?? 'sin_horario') => $item];
            });

            // Construir array de asistencias por cada horario del grupo
            $asistenciasPorHorario = $horarios->map(function ($horario) use ($asistenciaMap) {
                $asistencia = $asistenciaMap->get($horario->idHorario);
                return [
                    'idHorario' => $horario->idHorario,
                    'horaInicio' => $horario->horaInicio,
                    'horaFin' => $horario->horaFin,
                    'dia' => $horario->dia,
                    'asistencia' => $asistencia ? [
                        'tipo' => $asistencia->tipo,
                        'observacion' => $asistencia->observacion,
                    ] : null,
                ];
            });

            // También podría haber asistencias sin horario (si idHorario null)
            if ($asistenciaMap->has('sin_horario')) {
                $asistenciasPorHorario->push([
                    'idHorario' => null,
                    'horaInicio' => 'Sin horario',
                    'horaFin' => '',
                    'dia' => '',
                    'asistencia' => [
                        'tipo' => $asistenciaMap['sin_horario']->tipo,
                        'observacion' => $asistenciaMap['sin_horario']->observacion,
                    ],
                ]);
            }

            return [
                'id_inscripcion' => $inscripcion->idInscripcion,
                'id_usuario' => $user->id,
                'nombre_completo' => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
                'foto' => $user->foto,
                'carrera' => $carreraEstudiante?->nombreCarrera,
                'asistencias_hoy' => $asistenciasPorHorario,
            ];
        });

        return response()->json([
            'data' => [
                'lista_asistencia' => [
                    'id' => $lista->idListaAsistencia,
                    'fecha_inicio' => $lista->fecha_inicio,
                    'fecha_fin' => $lista->fecha_fin,
                ],
                'horarios' => $horarios->map(fn($h) => [
                    'idHorario' => $h->idHorario,
                    'horaInicio' => $h->horaInicio,
                    'horaFin' => $h->horaFin,
                    'dia' => $h->dia,
                ]),
                'estudiantes' => $estudiantes,
            ],
        ]);
    }

    /**
     * POST /api/docente/asistencia
     * Registra o actualiza la asistencia de un estudiante en una fecha y horario determinados.
     */
    public function registrarAsistencia(RegistrarAsistenciaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $fecha = $data['fecha'] ?? Carbon::now()->toDateString();
        $idHorario = $data['idHorario'] ?? null;

        $asistencia = ListaAsistenciaInscripcion::updateOrCreate(
            [
                'idInscripcion' => $data['id_inscripcion'],
                'idListaAsistencia' => $data['id_lista_asistencia'],
                'fecha' => $fecha,
                'idHorario' => $idHorario,
            ],
            [
                'tipo' => $data['tipo'],
                'observacion' => $data['observacion'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Asistencia registrada correctamente.',
            'data' => $asistencia,
        ], 200);
    }

    /**
     * POST /api/docente/asistencia/batch
     * Procesa un lote de asistencias de forma atómica.
     */
    public function batch(BatchAsistenciaRequest $request): JsonResponse
    {
        $asistencias = $request->validated()['asistencias'];
        $user = $request->user();
        $esAdmin = $user->hasRole('Administrador');

        // 1. Verificar que todas las listas de asistencia pertenezcan al docente
        $listaIds = array_unique(array_column($asistencias, 'id_lista_asistencia'));
        $listas = ListaAsistencia::with('grupoMateriaDocente')
            ->whereIn('idListaAsistencia', $listaIds)
            ->get()
            ->keyBy('idListaAsistencia');

        // Solo verificar pertenencia si NO es administrador
        if (!$esAdmin) {
            foreach ($listaIds as $idLista) {
                $lista = $listas->get($idLista);
                if (!$lista || $lista->grupoMateriaDocente->idDocente !== $user->id) {
                    return response()->json([
                        'message' => 'No tienes permiso para modificar la lista de asistencia proporcionada.',
                    ], 403);
                }
            }
        }

        // 2. Preparar datos para upsert
        $data = array_map(function ($item) {
            return [
                'idInscripcion' => $item['id_inscripcion'],
                'idListaAsistencia' => $item['id_lista_asistencia'],
                'tipo' => $item['tipo'],
                'observacion' => $item['observacion'] ?? null,
                'fecha' => $item['fecha'] ?? Carbon::now()->toDateString(),
                'idHorario' => $item['idHorario'] ?? null,
            ];
        }, $asistencias);

        // 3. Ejecutar upsert en transacción
        DB::transaction(function () use ($data) {
            ListaAsistenciaInscripcion::upsert(
                $data,
                ['idInscripcion', 'idListaAsistencia', 'fecha', 'idHorario'],
                ['tipo', 'observacion']
            );
        });

        return response()->json([
            'message' => 'Asistencias registradas correctamente.',
            'procesados' => count($asistencias),
            'errores' => [],
        ], 200);
    }

    /**
     * Obtiene o crea la ListaAsistencia según el régimen de la carrera.
     */
    private function obtenerOCrearListaAsistencia(GrupoMateriaDocente $gmd, Carrera $carrera): ListaAsistencia
    {
        $now = Carbon::now();

        switch ($carrera->regimen) {
            case 'Mensual':
                $inicio = $now->copy()->startOfMonth();
                $fin = $now->copy()->endOfMonth();
                break;
            case 'Anual':
                $inicio = $now->copy()->startOfYear();
                $fin = $now->copy()->endOfYear();
                break;
            case 'Semestral':
                if ($now->month <= 6) {
                    $inicio = Carbon::create($now->year, 1, 1);
                    $fin = Carbon::create($now->year, 6, 30);
                } else {
                    $inicio = Carbon::create($now->year, 7, 1);
                    $fin = Carbon::create($now->year, 12, 31);
                }
                break;
            default:
                $inicio = $now->copy()->startOfMonth();
                $fin = $now->copy()->endOfMonth();
                break;
        }

        return ListaAsistencia::firstOrCreate(
            [
                'id_grupo_materia_docente' => $gmd->idGrupoMateriaDocente,
                'fecha_inicio' => $inicio->format('Y-m-d H:i:s'),
                'fecha_fin' => $fin->format('Y-m-d H:i:s'),
            ],
            [
                'observacion' => "{$carrera->regimen} {$inicio->format('Y-m')}",
            ]
        );
    }

    public function reporteExcel(int $idGrupoMateriaDocente, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $datos = $this->obtenerDatosReporte($idGrupoMateriaDocente, $request);
        $filename = 'asistencia_' . $datos['paralelo'] . '_' . $datos['periodo'] . '.xlsx';

        $export = new AsistenciaExport($datos);
        return $export->stream($filename);
    }

    public function reportePdf(int $idGrupoMateriaDocente, Request $request)
    {
        $datos = $this->obtenerDatosReporte($idGrupoMateriaDocente, $request);
        $pdf = Pdf::loadView('reportes.asistencia', $datos)->setPaper('a4', 'landscape');
        $filename = 'asistencia_' . $datos['paralelo'] . '_' . $datos['periodo'] . '.pdf';

        return $pdf->download($filename);
    }

    private function obtenerDatosReporte(int $idGrupoMateriaDocente, Request $request): array
    {
        $user = $request->user();
        $gmd = GrupoMateriaDocente::with(['grupo', 'materia.carreras', 'docente.usuario'])
            ->findOrFail($idGrupoMateriaDocente);

        $esAdmin = $user->hasRole('Administrador');
        if (!$esAdmin && $gmd->idDocente !== $user->id) {
            abort(403, 'No estás asignado a este grupo.');
        }

        $carrera = $gmd->materia->carreras()->where('estadoCarrera', 'activo')->first();
        if (!$carrera) {
            abort(400, 'No se encontró una carrera activa para esta materia.');
        }

        $lista = ListaAsistencia::where('id_grupo_materia_docente', $idGrupoMateriaDocente)
            ->orderBy('fecha_inicio', 'desc')
            ->firstOrFail();

        $inscripciones = Inscripcion::with('usuario.carreras')
            ->where('idGrupo', $gmd->idGrupo)
            ->whereHas('usuario', fn($q) => $q->where('estado', 'ACTIVO'))
            ->orderBy('idInscripcion')
            ->get();

        $registros = ListaAsistenciaInscripcion::where('idListaAsistencia', $lista->idListaAsistencia)->get();

        $fechas = $registros
            ->pluck('fecha')
            ->map(fn($f) => $f instanceof Carbon ? $f->toDateString() : (string) $f)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Sesiones únicas = combinaciones (fecha, idHorario) — pueden ser varias por día
        $totalSesiones = $registros
            ->map(fn($r) => ($r->fecha instanceof Carbon ? $r->fecha->toDateString() : (string) $r->fecha) . '_' . ($r->idHorario ?? 'null'))
            ->unique()
            ->count();

        $pesos = ['Presente' => 1.0, 'Permiso' => 1.0, 'Atraso' => 0.5, 'Falta' => 0.0];

        $filas = $inscripciones->map(function ($inscripcion) use ($registros, $fechas, $totalSesiones, $pesos) {
            $user = $inscripcion->usuario;
            $carreraEst = $user->carreras()->where('estadoCarrera', 'activo')->first();

            $porFecha = $registros
                ->where('idInscripcion', $inscripcion->idInscripcion)
                ->mapWithKeys(function ($r) {
                    $fecha = $r->fecha instanceof Carbon
                        ? $r->fecha->toDateString()
                        : (string) $r->fecha;
                    $abrev = match ($r->tipo) {
                        'Presente' => 'P',
                        'Permiso'  => 'L',
                        'Falta'    => 'F',
                        'Atraso'   => 'A',
                        default    => strtoupper(substr($r->tipo, 0, 1)),
                    };
                    return [$fecha => $abrev];
                });

            $sumaPesos = $registros
                ->where('idInscripcion', $inscripcion->idInscripcion)
                ->sum(fn($r) => $pesos[$r->tipo] ?? 0.0);
            $porcentaje = $totalSesiones > 0 ? min(100.0, round(($sumaPesos / $totalSesiones) * 100, 1)) : 0;

            return [
                'nombre' => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
                'carrera' => $carreraEst?->nombreCarrera ?? '-',
                'asistencias' => $porFecha->toArray(),
                'porcentaje' => $porcentaje,
            ];
        })->values()->toArray();

        $docente = $gmd->docente?->usuario
            ? trim("{$gmd->docente->usuario->nombres} {$gmd->docente->usuario->apellidoPaterno} {$gmd->docente->usuario->apellidoMaterno}")
            : trim("{$user->nombres} {$user->apellidoPaterno}");

        return [
            'carrera' => $carrera->nombreCarrera,
            'asignatura' => $gmd->materia->nombreMateria,
            'docente' => $docente,
            'paralelo' => $gmd->grupo->nombre,
            'turno' => $gmd->grupo->turno ?? '-',
            'periodo' => $lista->observacion
                ?? ($lista->fecha_inicio->format('d/m/Y') . ' – ' . $lista->fecha_fin->format('d/m/Y')),
            'fecha_inicio' => $lista->fecha_inicio->format('d/m/Y'),
            'fecha_fin' => $lista->fecha_fin->format('d/m/Y'),
            'fechas' => $fechas,
            'filas' => $filas,
        ];
    }
}