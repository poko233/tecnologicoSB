<?php

namespace App\Http\Controllers;

use App\Models\CarreraUsuario;
use App\Models\Docente;
use App\Models\GrupoMateriaDocente;
use App\Models\Inscripcion;
use App\Models\ListaAsistencia;
use App\Models\ListaAsistenciaInscripcion;
use App\Models\ObservacionUsuario;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\QrService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    // Roles que se consideran "Administrativo" (no Docente ni Estudiante)
    private const ROLES_ADMIN = ['Administrador', 'Portero', 'Seguridad', 'Secretaria', 'Administrativo'];

    // Roles autorizados para el control de acceso institucional
    private const ROLES_CONTROL_ACCESO = ['Administrador', 'Portero', 'Seguridad'];

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/qr/decrypt
    // Desencripta un QR y devuelve datos básicos del usuario.
    // Middleware: auth:sanctum (cualquier usuario autenticado)
    // ─────────────────────────────────────────────────────────────────────────
    public function decrypt(Request $request): JsonResponse
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        $qrService = new QrService();
        $payload   = $qrService->decrypt($request->input('qr_data'));

        if (!$payload) {
            return response()->json(['message' => 'QR inválido o corrupto.'], 422);
        }

        $userId = $payload['user_id'] ?? null;

        if (!$userId) {
            return response()->json(['message' => 'QR no contiene user_id.'], 422);
        }

        $user = User::with('roles')->find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($user->estado !== 'ACTIVO') {
            return response()->json(['message' => 'El usuario está inactivo.'], 422);
        }

        $tipoPersona = $this->determinarTipoPersona($user);

        $carrera = null;
        $turno   = null;

        if ($tipoPersona === 'Estudiante') {
            ['carrera' => $carrera, 'turno' => $turno] = $this->getDatosEstudiante($user);
        }

        return response()->json([
            'user' => [
                'id'             => $user->id,
                'nombre_completo' => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
                'foto'           => $user->foto,
                'tipo'           => $tipoPersona,
                'carrera'        => $carrera,
                'turno'          => $turno,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/qr/asistencia
    // Registra asistencia de un estudiante escaneado por un docente.
    // Middleware: auth:sanctum, role:Docente
    // ─────────────────────────────────────────────────────────────────────────
    public function asistencia(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:user,id',
        ]);

        $docenteUser = $request->user();

        // 1. Verificar que el usuario autenticado sea docente activo
        $docente = Docente::where('idDocente', $docenteUser->id)
            ->where('estadoDocente', 'activo')
            ->first();

        if (!$docente) {
            return response()->json([
                'message' => 'No tienes un registro de docente activo.',
            ], 403);
        }

        // 2. Verificar que el user_id recibido sea un estudiante
        $estudiante = User::with('roles')->find($request->input('user_id'));

        if (!$estudiante || !$estudiante->hasRole('Estudiante')) {
            return response()->json([
                'message' => 'El usuario escaneado no es un estudiante.',
            ], 422);
        }

        // 3. Obtener asignaciones activas del docente (grupos activos)
        $asignaciones = GrupoMateriaDocente::with(['grupo', 'materia'])
            ->where('idDocente', $docenteUser->id)
            ->whereHas('grupo', fn($q) => $q->where('estado', 'activo'))
            ->get();

        if ($asignaciones->isEmpty()) {
            return response()->json([
                'message' => 'No tienes asignaciones activas en ningún grupo.',
            ], 403);
        }

        // 4. Buscar si el estudiante está inscrito en alguno de esos grupos
        $grupoIds = $asignaciones->pluck('idGrupo');

        $inscripcion = Inscripcion::where('idUsuario', $estudiante->id)
            ->whereIn('idGrupo', $grupoIds)
            ->first();

        if (!$inscripcion) {
            return response()->json([
                'message' => 'El estudiante no pertenece a ningún grupo del docente.',
            ], 403);
        }

        // 5. Determinar el GrupoMateriaDocente específico
        $asignacion = $asignaciones->firstWhere('idGrupo', $inscripcion->idGrupo);

        // 6. Crear o recuperar la ListaAsistencia mensual
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes    = Carbon::now()->endOfMonth();

        $lista = ListaAsistencia::firstOrCreate(
            [
                'id_grupo_materia_docente' => $asignacion->idGrupoMateriaDocente,
                'fecha_inicio'             => $inicioMes->format('Y-m-d H:i:s'),
                'fecha_fin'                => $finMes->format('Y-m-d H:i:s'),
            ],
            [
                'observacion' => 'Mes ' . $inicioMes->format('Y-m'),
            ]
        );

        // 7. Verificar si ya existe asistencia en esta lista para el estudiante
        $yaRegistrada = ListaAsistenciaInscripcion::where('idInscripcion', $inscripcion->idInscripcion)
            ->where('idListaAsistencia', $lista->idListaAsistencia)
            ->exists();

        if ($yaRegistrada) {
            return response()->json([
                'message' => 'Asistencia ya registrada para este mes.',
            ], 409);
        }

        // 8. Registrar asistencia
        $asistencia = ListaAsistenciaInscripcion::create([
            'tipo'              => 'Presente',
            'observacion'       => null,
            'idInscripcion'     => $inscripcion->idInscripcion,
            'idListaAsistencia' => $lista->idListaAsistencia,
        ]);

        $ahora  = Carbon::now();
        $grupo  = $asignacion->grupo;
        $materia = $asignacion->materia;

        return response()->json([
            'message'     => 'Asistencia registrada correctamente.',
            'asistencia'  => [
                'estudiante' => trim("{$estudiante->nombres} {$estudiante->apellidoPaterno} {$estudiante->apellidoMaterno}"),
                'materia'    => $materia?->nombreMateria,
                'grupo'      => $grupo ? "{$grupo->nombre} {$grupo->turno}" : null,
                'fecha'      => $ahora->toDateString(),
                'hora'       => $ahora->toTimeString(),
                'tipo'       => $asistencia->tipo,
            ],
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/qr/verify-access
    // Verifica estado del usuario escaneado y registra el acceso.
    // Middleware: auth:sanctum, role:Administrador,Portero,Seguridad
    // ─────────────────────────────────────────────────────────────────────────
    public function verifyAccess(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'       => 'required|integer|exists:user,id',
            'punto_control' => 'nullable|string|max:100',
        ]);

        $user = User::with('roles')->find($request->input('user_id'));

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($user->estado !== 'ACTIVO') {
            return response()->json(['message' => 'El usuario está inactivo.'], 403);
        }

        $tipoPersona = $this->determinarTipoPersona($user);

        // ── Docente o Administrativo: acceso directo ──────────────────────────
        if ($tipoPersona !== 'Estudiante') {
            $estadoMostrado = 'AUTORIZADO';
            $colorAlerta    = 'verde';
            $descripcion    = ucfirst(strtolower($tipoPersona)) . ' con acceso autorizado.';
            $cuotasPendientes       = 0;
            $observacionesActivas   = [];
            $carrera                = null;
            $turno                  = null;
        } else {
            // ── Estudiante: evaluar alertas ───────────────────────────────────
            ['carrera' => $carrera, 'turno' => $turno] = $this->getDatosEstudiante($user);

            $hoy = now()->toDateString();

            // a) Observaciones académicas activas
            $observacionesAcad = ObservacionUsuario::where('user_id', $user->id)
                ->activas()
                ->whereIn('tipo', ['abandono', 'reprobacion'])
                ->pluck('tipo')
                ->toArray();

            // b) Observaciones de deuda/bloqueo (fuerzan rojo)
            $obsDeudaBloqueo = ObservacionUsuario::where('user_id', $user->id)
                ->activas()
                ->whereIn('tipo', ['deuda', 'bloqueo'])
                ->exists();

            // c) Cuotas vencidas con estado 'Debe'
            $cuotasPendientes = \App\Models\Cuota::where('idUsuario', $user->id)
                ->where('estadoCuota', 'Debe')
                ->where('fecha_vencimiento', '<=', $hoy)
                ->count();

            // d) Todas las observaciones activas (para la respuesta)
            $observacionesActivas = ObservacionUsuario::where('user_id', $user->id)
                ->activas()
                ->pluck('tipo')
                ->toArray();

            // e) Determinar estado y color (prioridad: bloqueo > académica > financiera > pase)
            if ($obsDeudaBloqueo) {
                $estadoMostrado = 'LLAMAR A CONTABILIDAD';
                $colorAlerta    = 'rojo';
                $descripcion    = 'Estudiante con observación de deuda o bloqueo activa.';
            } elseif (in_array('abandono', $observacionesAcad)) {
                $estadoMostrado = 'HABLAR CON COORDINACIÓN';
                $colorAlerta    = 'naranja';
                $descripcion    = 'Estudiante con observación de abandono.';
            } elseif (in_array('reprobacion', $observacionesAcad)) {
                $estadoMostrado = 'PASAR A COORDINACIÓN';
                $colorAlerta    = 'naranja';
                $descripcion    = 'Estudiante con observación de reprobación.';
            } elseif ($cuotasPendientes >= 2) {
                $estadoMostrado = 'LLAMAR A CONTABILIDAD';
                $colorAlerta    = 'rojo';
                $descripcion    = "Estudiante con {$cuotasPendientes} cuotas pendientes.";
            } elseif ($cuotasPendientes === 1) {
                $estadoMostrado = 'PASAR POR CONTABILIDAD';
                $colorAlerta    = 'amarillo';
                $descripcion    = 'Estudiante con 1 cuota pendiente.';
            } else {
                $estadoMostrado = 'PASE';
                $colorAlerta    = 'verde';
                $descripcion    = 'Estudiante al día.';
            }
        }

        // Registrar el acceso en auditoría
        $registro = RegistroAcceso::create([
            'user_id'        => $user->id,
            'tipo_persona'   => $tipoPersona,
            'estado_mostrado' => $estadoMostrado,
            'color_alerta'   => $colorAlerta,
            'punto_control'  => $request->input('punto_control'),
            'fecha_hora'     => now(),
        ]);

        return response()->json([
            'alerta' => [
                'color'       => $colorAlerta,
                'mensaje'     => $estadoMostrado,
                'descripcion' => $descripcion,
            ],
            'usuario' => [
                'id'                   => $user->id,
                'nombre_completo'      => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
                'foto'                 => $user->foto,
                'tipo'                 => $tipoPersona,
                'carrera'              => $carrera ?? null,
                'turno'                => $turno ?? null,
                'cuotas_pendientes'    => $cuotasPendientes ?? 0,
                'observaciones_activas' => $observacionesActivas ?? [],
            ],
            'registro_id' => $registro->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Determina el tipo de persona según sus roles y su registro en Docente.
     */
    private function determinarTipoPersona(User $user): string
    {
        $roles = $user->roles->pluck('rol')->toArray();

        // Docente con registro activo tiene prioridad
        if (in_array('Docente', $roles)) {
            $esDocenteActivo = Docente::where('idDocente', $user->id)
                ->where('estadoDocente', 'activo')
                ->exists();
            if ($esDocenteActivo) {
                return 'Docente';
            }
        }

        // Roles administrativos
        if (!empty(array_intersect($roles, self::ROLES_ADMIN))) {
            return 'Administrativo';
        }

        return 'Estudiante';
    }

    /**
     * Obtiene la carrera activa y el turno del grupo activo en que está inscrito el estudiante.
     */
    private function getDatosEstudiante(User $user): array
    {
        // Carrera activa vía CarreraUsuario
        $carreraUsuario = CarreraUsuario::where('idUsuario', $user->id)
            ->with(['carrera' => fn($q) => $q->where('estadoCarrera', 'activo')])
            ->get()
            ->first(fn($cu) => $cu->carrera !== null);

        $carrera = $carreraUsuario?->carrera?->nombreCarrera;

        // Turno del grupo activo en que está inscrito
        $inscripcion = Inscripcion::where('idUsuario', $user->id)
            ->with(['grupo' => fn($q) => $q->where('estado', 'activo')])
            ->get()
            ->first(fn($i) => $i->grupo !== null);

        $turno = $inscripcion?->grupo?->turno;

        return ['carrera' => $carrera, 'turno' => $turno];
    }
}
