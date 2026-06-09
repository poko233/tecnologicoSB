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


class DocenteAsistenciaController extends Controller
{
    /**
     * GET /api/docente/grupos-asignados
     * Retorna los grupos activos asignados al docente, ordenados del más reciente al más antiguo.
     */
    public function gruposAsignados(Request $request): JsonResponse
    {
        $user = $request->user();

        $grupos = GrupoMateriaDocente::with([
            'grupo' => fn($q) => $q->where('estado', 'activo'),
            'materia.carreras' => fn($q) => $q->where('estadoCarrera', 'activo'),
        ])
            ->where('idDocente', $user->id)
            ->whereHas('grupo', fn($q) => $q->where('estado', 'activo'))
            ->orderBy('created_at', 'desc')
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
        $gmd = GrupoMateriaDocente::with(['grupo.horarios', 'materia.carreras'])->findOrFail($idGrupoMateriaDocente);

        if ($gmd->idDocente !== $request->user()->id) {
            return response()->json(['message' => 'No estás asignado a este grupo.'], 403);
        }

        $carrera = $gmd->materia->carreras()->where('estadoCarrera', 'activo')->first();
        if (!$carrera) {
            return response()->json(['message' => 'No se encontró una carrera activa asociada a esta materia.'], 400);
        }

        $lista = $this->obtenerOCrearListaAsistencia($gmd, $carrera);

        // Horarios del grupo (para ofrecer las posibles sesiones)
        $horarios = $gmd->grupo->horarios;  // relación muchos a muchos a través de GrupoHorario

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
                        'dia' => $h->dia,   // ← agregado
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
                    'dia' => $horario->dia,   // ← agregado
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
                    'dia' => '',      // ← sin día cuando no hay horario
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
                    'dia' => $h->dia,   // ← agregado
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

        // 1. Verificar que todas las listas de asistencia pertenezcan al docente
        $listaIds = array_unique(array_column($asistencias, 'id_lista_asistencia'));
        $listas = ListaAsistencia::with('grupoMateriaDocente')
            ->whereIn('idListaAsistencia', $listaIds)
            ->get()
            ->keyBy('idListaAsistencia');

        foreach ($listaIds as $idLista) {
            $lista = $listas->get($idLista);
            if (!$lista || $lista->grupoMateriaDocente->idDocente !== $user->id) {
                return response()->json([
                    'message' => 'No tienes permiso para modificar la lista de asistencia proporcionada.',
                ], 403);
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

    public function reporteCsv(int $idGrupoMateriaDocente, Request $request): StreamedResponse
    {
        $datos    = $this->obtenerDatosReporte($idGrupoMateriaDocente, $request);
        $filename = 'asistencia_' . $datos['paralelo'] . '_' . $datos['periodo'] . '.csv';
 
        return response()->streamDownload(function () use ($datos) {
            $handle = fopen('php://output', 'w');
 
            // BOM para que Excel abra tildes correctamente
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
 
            // Cabecera informativa
            fputcsv($handle, ['Carrera',    $datos['carrera']]);
            fputcsv($handle, ['Asignatura', $datos['asignatura']]);
            fputcsv($handle, ['Docente',    $datos['docente']]);
            fputcsv($handle, ['Paralelo',   $datos['paralelo']]);
            fputcsv($handle, ['Período',    $datos['periodo']]);
            fputcsv($handle, []);
 
            // Encabezados de columnas
            fputcsv($handle, ['N°', 'Estudiante', 'Carrera', ...$datos['fechas'], '% Asistencia']);
 
            // Filas de estudiantes
            foreach ($datos['filas'] as $i => $fila) {
                $row = [$i + 1, $fila['nombre'], $fila['carrera']];
                foreach ($datos['fechas'] as $fecha) {
                    $row[] = $fila['asistencias'][$fecha] ?? '-';
                }
                $row[] = $fila['porcentaje'] . '%';
                fputcsv($handle, $row);
            }
 
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
 
    public function reportePdf(int $idGrupoMateriaDocente, Request $request)
    {
        $datos    = $this->obtenerDatosReporte($idGrupoMateriaDocente, $request);
        $pdf      = Pdf::loadView('reportes.asistencia', $datos)->setPaper('a4', 'landscape');
        $filename = 'asistencia_' . $datos['paralelo'] . '_' . $datos['periodo'] . '.pdf';
 
        return $pdf->download($filename);
    }
 
    private function obtenerDatosReporte(int $idGrupoMateriaDocente, Request $request): array
    {
        $user = $request->user();
 
        $gmd = GrupoMateriaDocente::with(['grupo', 'materia.carreras', 'docente.usuario'])
            ->findOrFail($idGrupoMateriaDocente);
 
        if ($gmd->idDocente !== $user->id) {
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
 
        // Fechas únicas ordenadas
        $fechas = $registros
            ->pluck('fecha')
            ->map(fn($f) => $f instanceof Carbon ? $f->toDateString() : (string) $f)
            ->unique()
            ->sort()
            ->values()
            ->toArray();
 
        // Filas por estudiante
        $filas = $inscripciones->map(function ($inscripcion) use ($registros, $fechas) {
            $user       = $inscripcion->usuario;
            $carreraEst = $user->carreras()->where('estadoCarrera', 'activo')->first();
 
            $porFecha = $registros
                ->where('idInscripcion', $inscripcion->idInscripcion)
                ->mapWithKeys(function ($r) {
                    $fecha = $r->fecha instanceof Carbon
                        ? $r->fecha->toDateString()
                        : (string) $r->fecha;
                    $abrev = match (strtolower($r->tipo)) {
                        'presente'    => 'P',
                        'ausente'     => 'A',
                        'tardanza'    => 'T',
                        'justificado' => 'J',
                        default       => strtoupper(substr($r->tipo, 0, 1)),
                    };
                    return [$fecha => $abrev];
                });
 
            $totalFechas = count($fechas);
            $presentes   = $registros
                ->where('idInscripcion', $inscripcion->idInscripcion)
                ->whereIn('tipo', ['presente', 'justificado', 'Presente', 'Justificado'])
                ->count();
            $porcentaje  = $totalFechas > 0 ? round(($presentes / $totalFechas) * 100, 1) : 0;
 
            return [
                'nombre'      => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
                'carrera'     => $carreraEst?->nombreCarrera ?? '-',
                'asistencias' => $porFecha->toArray(),
                'porcentaje'  => $porcentaje,
            ];
        })->values()->toArray();
 
        $docente = $gmd->docente?->usuario
            ? trim("{$gmd->docente->usuario->nombres} {$gmd->docente->usuario->apellidoPaterno} {$gmd->docente->usuario->apellidoMaterno}")
            : trim("{$user->nombres} {$user->apellidoPaterno}");
 
        return [
            'carrera'      => $carrera->nombreCarrera,
            'asignatura'   => $gmd->materia->nombreMateria,
            'docente'      => $docente,
            'paralelo'     => $gmd->grupo->nombre,
            'turno'        => $gmd->grupo->turno ?? '-',   // ← SOLO ESTO SE AGREGA
            'periodo'      => $lista->observacion
                            ?? ($lista->fecha_inicio->format('d/m/Y') . ' – ' . $lista->fecha_fin->format('d/m/Y')),
            'fecha_inicio' => $lista->fecha_inicio->format('d/m/Y'),
            'fecha_fin'    => $lista->fecha_fin->format('d/m/Y'),
            'fechas'       => $fechas,
            'filas'        => $filas,
        ];
    }
}