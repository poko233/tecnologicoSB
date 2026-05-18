<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuotaController extends Controller
{
    /**
     * Busca estudiantes por CI, nombre o matrícula con paginación.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Validar parámetros
        $request->validate([
            'search' => 'nullable|string|min:1|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $searchTerm = $request->input('search', '');
        $perPage = $request->input('per_page', 15);

        // Subconsulta para obtener el ID del rol 'Estudiante'
        $rolEstudianteId = DB::table('rol')
            ->where('rol', 'Estudiante') // Ajusta el nombre exacto si es diferente (ej: 'ESTUDIANTE')
            ->value('id');

        if (!$rolEstudianteId) {
            return response()->json([
                'message' => 'No se encontró el rol de estudiante en el sistema.'
            ], 500);
        }

        // Query base: usuarios que tienen el rol de estudiante
        $query = DB::table('user')
            ->join('user_rol', 'user.id', '=', 'user_rol.id_user')
            ->where('user_rol.id_rol', $rolEstudianteId)
            ->select(
                'user.id',
                'user.ci',
                'user.nombres',
                'user.apellidoPaterno',
                'user.apellidoMaterno',
                'user.matricula',
                'user.email',
                'user.telefono',
                'user.celular',
                'user.foto',
                'user.estado'
            )
            ->distinct(); // Por si un usuario tiene múltiples asignaciones de rol (aunque la migración tiene unique)

        // Aplicar búsqueda si hay término
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                // Búsqueda por CI
                $q->where('user.ci', 'LIKE', "%{$searchTerm}%")
                    // Búsqueda por matrícula
                    ->orWhere('user.matricula', 'LIKE', "%{$searchTerm}%")
                    // Búsqueda por nombre completo (concatenando)
                    ->orWhereRaw(
                        "CONCAT(user.nombres, ' ', user.apellidoPaterno, ' ', user.apellidoMaterno) LIKE ?",
                        ["%{$searchTerm}%"]
                    );
            });
        }

        // Paginar resultados
        $students = $query->paginate($perPage);

        // Transformar resultados para que el frontend reciba un formato limpio
        $students->getCollection()->transform(function ($student) {
            return [
                'id' => $student->id,
                'ci' => $student->ci,
                'nombres' => $student->nombres,
                'apellidoPaterno' => $student->apellidoPaterno,
                'apellidoMaterno' => $student->apellidoMaterno,
                'nombreCompleto' => trim("{$student->nombres} {$student->apellidoPaterno} {$student->apellidoMaterno}"),
                'matricula' => $student->matricula,
                'email' => $student->email,
                'telefono' => $student->telefono,
                'celular' => $student->celular,
                'foto' => $student->foto,
                'estado' => $student->estado,
            ];
        });

        return response()->json($students);
    }
    /**
     * Obtener detalle completo de un estudiante (incluyendo carrera, plan de pagos y cuotas)
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // 1. Datos básicos del estudiante
        $estudiante = DB::table('user')
            ->where('id', $id)
            ->select(
                'id',
                'usuario',
                'ci',
                'nombres',
                'apellidoPaterno',
                'apellidoMaterno',
                'genero',
                'fecha_nac',
                'email',
                'telefono',
                'celular',
                'direccion',
                'matricula as user_matricula',
                'expedido',
                'foto',
                'estado'
            )
            ->first();

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        // 2. Carrera actual (tomamos la primera inscripción activa, o la más reciente)
        $carrera = DB::table('CarreraUsuario')
            ->join('Carrera', 'CarreraUsuario.idCarrera', '=', 'Carrera.idCarrera')
            ->where('CarreraUsuario.idUsuario', $id)
            ->select('Carrera.nombreCarrera', 'Carrera.codigo', 'Carrera.duracion')
            ->first();

        // 3. Plan de pago activo (gestión actual = 2026, estado 'activo' o el más reciente)
        $gestionActual = date('Y'); // 2026
        $planPago = DB::table('PlanPago')
            ->where('idUsuario', $id)
            ->where('gestion', $gestionActual)
            ->orderByRaw("FIELD(estado, 'activo', 'pendiente_matricula', 'inactivo')")
            ->orderBy('id', 'desc')
            ->first();

        // Si no hay plan para la gestión actual, buscamos el más reciente de cualquier gestión
        if (!$planPago) {
            $planPago = DB::table('PlanPago')
                ->where('idUsuario', $id)
                ->orderBy('gestion', 'desc')
                ->orderBy('id', 'desc')
                ->first();
        }

        // 4. Determinar número de matrícula final (priorizar user.matricula, luego planPago.matricula_numero)
        $numeroMatricula = $estudiante->user_matricula ?? ($planPago->matricula_numero ?? null);

        // 5. Cuotas asociadas al plan (si existe plan)
        $cuotas = [];
        if ($planPago) {
            $cuotas = DB::table('Cuota')
                ->where('idPlanPago', $planPago->id)
                ->orderByRaw("FIELD(tipo, 'MATRICULA', 'MENSUAL')")
                ->orderBy('numeroCuota', 'asc')
                ->get();
        }

        // 6. Construir respuesta
        return response()->json([
            'estudiante' => [
                'id' => $estudiante->id,
                'usuario' => $estudiante->usuario,
                'ci' => $estudiante->ci,
                'nombres' => $estudiante->nombres,
                'apellidoPaterno' => $estudiante->apellidoPaterno,
                'apellidoMaterno' => $estudiante->apellidoMaterno,
                'nombreCompleto' => trim("{$estudiante->nombres} {$estudiante->apellidoPaterno} {$estudiante->apellidoMaterno}"),
                'genero' => $estudiante->genero,
                'fechaNacimiento' => $estudiante->fecha_nac,
                'email' => $estudiante->email,
                'telefono' => $estudiante->telefono,
                'celular' => $estudiante->celular,
                'direccion' => $estudiante->direccion,
                'matricula' => $numeroMatricula,
                'expedido' => $estudiante->expedido,
                'foto' => $estudiante->foto,
                'estado' => $estudiante->estado,
            ],
            'carrera' => $carrera ? [
                'nombre' => $carrera->nombreCarrera,
                'codigo' => $carrera->codigo,
                'duracion' => $carrera->duracion,
            ] : null,
            'plan_pago' => $planPago ? [
                'id' => $planPago->id,
                'gestion' => $planPago->gestion,
                'matricula_economica' => $planPago->matricula_economica,
                'numero_cuotas' => $planPago->numero_cuotas,
                'monto_cuota_promocion' => $planPago->monto_cuota_promocion,
                'monto_cuota_normal' => $planPago->monto_cuota_normal,
                'matricula_numero' => $planPago->matricula_numero,
                'estado' => $planPago->estado,
            ] : null,
            'cuotas' => $cuotas->map(function ($cuota) {
                return [
                    'id' => $cuota->idCuota,
                    'tipo' => $cuota->tipo,
                    'numeroCuota' => $cuota->numeroCuota,
                    'monto' => $cuota->monto,
                    'descuento' => $cuota->descuento,
                    'fecha_vencimiento' => $cuota->fecha_vencimiento,
                    'estadoCuota' => $cuota->estadoCuota,
                    'fecha_pago' => $cuota->fecha_pago,
                ];
            }),
        ]);
    }
}