<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CarreraUsuario;
use App\Models\Docente;
use App\Models\Inscripcion;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/qr/verify-access
    // Verifica estado del usuario escaneado (por user_id) y registra el acceso.
    // Middleware: auth:sanctum, role:Administrador,Portero,Seguridad
    // ─────────────────────────────────────────────────────────────────────────
    public function verifyAccess(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:user,id',
            'punto_control' => 'nullable|string|max:100',
        ]);

        $user = User::with('roles')->find($request->input('user_id'));

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($user->estado !== 'ACTIVO') {
            return response()->json(['message' => 'El usuario está inactivo.'], 403);
        }

        return $this->verifyAccessForUser($user, $request->input('punto_control'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/qr/verify-access-ci  (NUEVO)
    // Verifica acceso por CI (carnet de identidad) en lugar de user_id.
    // Middleware: auth:sanctum, role:Administrador,Portero,Seguridad
    // ─────────────────────────────────────────────────────────────────────────
    public function verifyAccessByCi(Request $request): JsonResponse
    {
        $request->validate([
            'ci' => 'required|string',
            'punto_control' => 'nullable|string|max:100',
        ]);

        $ci = $request->input('ci');

        $user = User::where('ci', $ci)->first();

        if (!$user || $user->estado !== 'ACTIVO') {
            return response()->json([
                'message' => 'Usuario no encontrado o inactivo',
            ], 404);
        }

        return $this->verifyAccessForUser($user, $request->input('punto_control'));
    }
    /**
     * POST /api/qr/debug-generate
     * Endpoint de depuración para generar/actualizar el QR de un usuario
     * y devolver el resultado o el error detallado.
     */
    public function debugGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:user,id',
        ]);

        // ---- DIAGNÓSTICO DE QR_SECRET_KEY ----
        $envValue = env('QR_SECRET_KEY', '');
        $envLength = strlen($envValue);
        $envFirst = substr($envValue, 0, 10);

        $getenvValue = getenv('QR_SECRET_KEY');
        $getenvLength = $getenvValue !== false ? strlen($getenvValue) : 'no definida';
        $getenvFirst = $getenvValue !== false ? substr($getenvValue, 0, 10) : '';

        $serverValue = $_SERVER['QR_SECRET_KEY'] ?? 'no definida';
        $serverLength = $serverValue !== 'no definida' ? strlen($serverValue) : 'no definida';
        // ---------------------------------------

        try {
            $qrService = new QrService();   // aquí saltará el error si la clave es incorrecta
            $qrCode = $qrService->generateQrImage($request->input('user_id'));

            $user = User::find($request->input('user_id'));
            if ($user) {
                $user->updateQuietly(['codigo_qr' => $qrCode]);
            }

            return response()->json([
                'success' => true,
                'qr_length' => strlen($qrCode),
                'qr_preview' => substr($qrCode, 0, 100) . '...',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
                'diagnostico' => [
                    'env' => [
                        'longitud' => $envLength,
                        'primeros10' => $envFirst,
                    ],
                    'getenv' => [
                        'longitud' => $getenvLength,
                        'primeros10' => $getenvFirst,
                    ],
                    '_SERVER' => [
                        'longitud' => $serverLength,
                    ],
                ],
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
    /**
     * POST /api/qr/regenerate-all
     * Regenera todos los QR de los usuarios (ACTIVOS).
     * Middleware: auth:sanctum, role:Administrador
     *//**
     * POST /api/qr/regenerate-all
     * Regenera todos los QR de los usuarios (ACTIVOS).
     * Middleware: auth:sanctum, role:Administrador
     */
    public function regenerateAll(Request $request): JsonResponse
    {
        // Aumentar tiempo máximo a 5 minutos
        set_time_limit(300);

        $total = User::where('estado', 'ACTIVO')->count();
        $generados = 0;
        $errores = [];

        try {
            $qrService = new QrService();

            // Usamos cursor() en lugar de chunk para evitar problemas de hidratación
            // y reducir el consumo de memoria.
            $usuarios = User::where('estado', 'ACTIVO')->cursor();

            foreach ($usuarios as $user) {
                try {
                    $qrDataUri = $qrService->generateQrImage($user->id);

                    // Actualizar sin disparar eventos (updateQuietly no siempre funciona si no es Eloquent)
                    User::withoutEvents(function () use ($user, $qrDataUri) {
                        $user->update(['codigo_qr' => $qrDataUri]);
                    });

                    $generados++;
                } catch (\Throwable $e) {
                    $errores[] = [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'total_usuarios_activos' => $total,
                'qr_generados' => $generados,
                'errores' => $errores,
                'mensaje' => "Se regeneraron {$generados} QR de {$total} usuarios activos.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODO PRIVADO COMÚN: lógica de verificación de acceso para un User dado
    // ─────────────────────────────────────────────────────────────────────────
    private function verifyAccessForUser(User $user, ?string $puntoControl = null): JsonResponse
    {
        $tipoPersona = $this->determinarTipoPersona($user);
        $datosUsuario = $this->getDatosUsuario($user, $tipoPersona);

        // ── Docente o Administrativo: acceso directo ──────────────────────────
        if ($tipoPersona !== 'Estudiante') {
            $estadoMostrado = 'AUTORIZADO';
            $colorAlerta = 'verde';
            $descripcion = ucfirst(strtolower($tipoPersona)) . ' con acceso autorizado.';
            $cuotasPendientes = 0;
        } else {
            // ── Estudiante: evaluar únicamente cuotas pendientes ──────────────
            $hoy = now()->toDateString();

            $cuotasPendientes = \App\Models\Cuota::where('idUsuario', $user->id)
                ->where('estadoCuota', 'Debe')
                ->where('fecha_vencimiento', '<', $hoy)
                ->count();

            if ($cuotasPendientes >= 2) {
                $estadoMostrado = 'LLAMAR A CONTABILIDAD';
                $colorAlerta = 'rojo';
                $descripcion = "Estudiante con {$cuotasPendientes} cuotas pendientes.";
            } elseif ($cuotasPendientes === 1) {
                $estadoMostrado = 'PASAR POR CONTABILIDAD';
                $colorAlerta = 'amarillo';
                $descripcion = 'Estudiante con 1 cuota pendiente.';
            } else {
                $estadoMostrado = 'PASE';
                $colorAlerta = 'verde';
                $descripcion = 'Estudiante al día.';
            }
        }

        // Registrar el acceso en auditoría
        // Obtener ubicación automáticamente (o fallback)
        $ubicacion = $this->obtenerUbicacion();

        $registro = RegistroAcceso::create([
            'user_id' => $user->id,
            'tipo_persona' => $tipoPersona,
            'estado_mostrado' => $estadoMostrado,
            'color_alerta' => $colorAlerta,
            'punto_control' => $puntoControl ?: $ubicacion,   // el frontend puede sobrescribir si quiere
            'fecha_hora' => now(),
        ]);
        // Construir respuesta
        $respuestaUsuario = array_merge($datosUsuario, [
            'id' => $user->id,
            'tipo' => $tipoPersona,
            'cuotas_pendientes' => $cuotasPendientes ?? 0,
        ]);

        return response()->json([
            'alerta' => [
                'color' => $colorAlerta,
                'mensaje' => $estadoMostrado,
                'descripcion' => $descripcion,
            ],
            'usuario' => $respuestaUsuario,
            'registro_id' => $registro->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Determina el tipo de persona según sus roles.
     * - Si tiene rol "Docente" y registro activo → Docente.
     * - Si tiene rol "Estudiante" → Estudiante.
     * - Cualquier otro rol → Administrativo.
     */
    private function determinarTipoPersona(User $user): string
    {
        $roles = $user->roles->pluck('rol')->toArray();

        if (in_array('Docente', $roles)) {
            $esDocenteActivo = Docente::where('idDocente', $user->id)
                ->where('estadoDocente', 'activo')
                ->exists();
            if ($esDocenteActivo) {
                return 'Docente';
            }
        }

        if (in_array('Estudiante', $roles)) {
            return 'Estudiante';
        }

        return 'Administrativo';
    }

    /**
     * Obtiene los datos personales base y los adicionales según el tipo de usuario.
     */
    private function getDatosUsuario(User $user, string $tipoPersona): array
    {
        // Datos comunes para cualquier tipo
        $base = [
            'nombre_completo' => trim("{$user->nombres} {$user->apellidoPaterno} {$user->apellidoMaterno}"),
            'nombres' => $user->nombres,
            'apellido_paterno' => $user->apellidoPaterno,
            'apellido_materno' => $user->apellidoMaterno,
            'genero' => $user->genero,
            'email' => $user->email,
            'ci' => $user->ci,
            'celular' => $user->celular,
            'direccion' => $user->direccion,
            'foto' => $user->foto,
        ];

        // Datos específicos según tipo
        $extras = [];

        if ($tipoPersona === 'Estudiante') {
            // Carrera activa (sin turno)
            $carreraUsuario = CarreraUsuario::where('idUsuario', $user->id)
                ->with(['carrera' => fn($q) => $q->where('estadoCarrera', 'activo')])
                ->get()
                ->first(fn($cu) => $cu->carrera !== null);

            $extras['carrera'] = $carreraUsuario?->carrera?->nombreCarrera;
        } elseif ($tipoPersona === 'Docente') {
            $docente = Docente::where('idDocente', $user->id)->first();
            $extras['profesion'] = $docente?->profesion;
            // Si se desea añadir abreviatura, descomentar:
            // $extras['abreviatura'] = $docente?->abreviatura;
        }

        return array_merge($base, $extras);
    }
    private function obtenerUbicacion(): string
    {
        try {
            // La IP del cliente que hace la petición
            $ip = request()->ip();

            // Intentar ip-api.com (HTTP, pero desde el servidor no hay problema)
            $response = file_get_contents("http://ip-api.com/json/{$ip}?fields=city,country,lat,lon,isp,query");
            if ($response === false) {
                throw new \Exception('Sin respuesta de ip-api');
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['city'])) {
                throw new \Exception('Respuesta inválida de ip-api');
            }

            $parts = array_filter([
                $data['city'] ?? null,
                $data['country'] ?? null,
                $data['isp'] ?? null,
                $data['query'] ?? null,
            ]);
            return implode(', ', $parts);
        } catch (\Throwable $e) {
            \Log::warning('No se pudo obtener ubicación por IP: ' . $e->getMessage());
            return 'Entrada principal a las instalaciones';
        }
    }
}