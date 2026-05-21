<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuotaController extends Controller
{
    /**
     * Busca estudiantes por CI, nombre o matrícula con paginación.
     */
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|min:1|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $searchTerm = $request->input('search', '');
        $perPage = $request->input('per_page', 15);

        // ID del rol 'Estudiante'
        $rolEstudianteId = DB::table('rol')
            ->where('rol', 'Estudiante')
            ->value('id');

        if (!$rolEstudianteId) {
            return response()->json([
                'message' => 'No se encontró el rol de estudiante en el sistema.'
            ], 500);
        }

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
            ->distinct();

        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('user.ci', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('user.matricula', 'LIKE', "%{$searchTerm}%")
                    ->orWhereRaw(
                        "CONCAT(user.nombres, ' ', user.apellidoPaterno, ' ', user.apellidoMaterno) LIKE ?",
                        ["%{$searchTerm}%"]
                    );
            });
        }

        $students = $query->paginate($perPage);

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
     * Obtener información básica de un estudiante (sin planes de pago).
     * Se mantiene por compatibilidad con el frontend actual.
     */
    public function show($id)
    {
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

        // Obtener la primera carrera a la que está inscrito (opcional)
        $carrera = DB::table('CarreraUsuario')
            ->join('Carrera', 'CarreraUsuario.idCarrera', '=', 'Carrera.idCarrera')
            ->where('CarreraUsuario.idUsuario', $id)
            ->select('Carrera.nombreCarrera', 'Carrera.codigo', 'Carrera.duracion')
            ->first();

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
                'matricula' => $estudiante->user_matricula,
                'expedido' => $estudiante->expedido,
                'foto' => $estudiante->foto,
                'estado' => $estudiante->estado,
            ],
            'carrera' => $carrera ? [
                'nombre' => $carrera->nombreCarrera,
                'codigo' => $carrera->codigo,
                'duracion' => $carrera->duracion,
            ] : null,
            // Campos obsoletos pero se dejan vacíos para no romper el frontend antiguo
            'plan_pago' => null,
            'cuotas' => [],
        ]);
    }

    /**
     * Lista las carreras en las que está inscrito un estudiante.
     * GET /estudiantes/{id}/carreras
     */
    public function carreras($id)
    {
        $carreras = DB::table('CarreraUsuario')
            ->join('Carrera', 'CarreraUsuario.idCarrera', '=', 'Carrera.idCarrera')
            ->where('CarreraUsuario.idUsuario', $id)
            ->select(
                'Carrera.idCarrera',
                'Carrera.nombreCarrera',
                'Carrera.codigo',
                'Carrera.regimen',
                'Carrera.duracion',
                'Carrera.cuota_mensual',
                'Carrera.costo_matricula',
                'Carrera.costo',
                'Carrera.cuotas_por_anio'
            )
            ->get();

        return response()->json($carreras);
    }

    /**
     * Obtiene las cuotas de un estudiante para una carrera específica.
     * GET /estudiantes/{id}/carreras/{carreraId}/cuotas
     */
    public function cuotasPorCarrera($usuarioId, $carreraId)
    {
        $cuotas = DB::table('Cuota')
            ->where('idUsuario', $usuarioId)
            ->where('idCarrera', $carreraId)
            ->orderByRaw("FIELD(tipo, 'MATRICULA', 'MENSUAL')")
            ->orderBy('numeroCuota', 'asc')
            ->get();

        return response()->json($cuotas);
    }
}