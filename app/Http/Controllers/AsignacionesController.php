<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AsignacionesController extends Controller
{
public function estudiantes()
{
    $estudiantes = DB::table('user as u')
        ->join('user_rol as ur', 'ur.id_user', '=', 'u.id')
        ->leftJoin('Estudiante as est', 'est.id_usuario', '=', 'u.id')
        ->where('ur.id_rol', 2)
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
        ->orderBy('u.apellidoPaterno')
        ->orderBy('u.apellidoMaterno')
        ->orderBy('u.nombres')
        ->get();

    return response()->json([
        'estudiantes' => $estudiantes,
    ]);
}

    public function detalleEstudiante($idUsuario)
    {
        $estudiante = User::findOrFail($idUsuario);

        $documentos = DB::table('DocumentoEstudiante')
            ->where('idUsuario', $idUsuario)
            ->select(
                'idDocumentoEstudiante',
                'nombreDocumento',
                'estadoDocumento',
                'idUsuario'
            )
            ->get();

        $documentosRequeridos = [
            'Carnet de identidad',
            'Certificado de nacimiento',
            'Título de bachiller',
        ];

        $documentosPresentados = $documentos
            ->where('estadoDocumento', 1)
            ->pluck('nombreDocumento')
            ->map(fn ($v) => mb_strtolower(trim($v)))
            ->toArray();

        $documentosPendientes = collect($documentosRequeridos)
            ->filter(function ($doc) use ($documentosPresentados) {
                return !in_array(mb_strtolower($doc), $documentosPresentados, true);
            })
            ->values();

        $inscripciones = DB::table('Inscripcion as i')
            ->join('Grupo as g', 'g.idGrupo', '=', 'i.idGrupo')
            ->leftJoin('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'g.idGrupo')
            ->leftJoin('Materia as m', 'm.idMateria', '=', 'gmd.idMateria')
            ->leftJoin('CarreraMateria as cm', 'cm.idMateria', '=', 'm.idMateria')
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
            ->orderBy('m.semestre')
            ->orderBy('m.nombreMateria')
            ->get();

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
            ->get();

        return response()->json([
            'estudiante' => $estudiante,
            'carreras' => $carreras,
            'documentos' => $documentos,
            'documentosPendientes' => $documentosPendientes,
            'debeDocumentos' => $documentosPendientes->count() > 0,
            'inscripciones' => $inscripciones,
        ]);
    }

    public function materiasSemestreUno(Request $request, $idUsuario)
    {
        $idCarrera = $request->query('idCarrera');

        $query = DB::table('Materia as m')
            ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'm.idMateria')
            ->join('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
            ->join('GrupoMateriaDocente as gmd', 'gmd.idMateria', '=', 'm.idMateria')
            ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
            ->where('m.semestre', 1)
            ->where('m.estado', 'activo')
            ->where('g.estado', 'activo')
            ->where('g.cupos', '>', 0);

        if ($idCarrera) {
            $query->where('c.idCarrera', $idCarrera);
        } else {
            $query->whereIn('c.idCarrera', function ($sub) use ($idUsuario) {
                $sub->select('idCarrera')
                    ->from('CarreraUsuario')
                    ->where('idUsuario', $idUsuario);
            });
        }

        $materias = $query
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
            ->orderBy('m.nombreMateria')
            ->orderBy('g.idGrupo')
            ->get()
            ->groupBy('idMateria')
            ->map(function ($items) use ($idUsuario) {
                $primero = $items->first();

                $yaInscrito = DB::table('Inscripcion as i')
                    ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'i.idGrupo')
                    ->where('i.idUsuario', $idUsuario)
                    ->where('gmd.idMateria', $primero->idMateria)
                    ->exists();

                $grupo = $items->first();

                return [
                    'idMateria' => $primero->idMateria,
                    'nombreMateria' => $primero->nombreMateria,
                    'codigoMateria' => $primero->codigoMateria,
                    'semestre' => $primero->semestre,
                    'idCarrera' => $primero->idCarrera,
                    'nombreCarrera' => $primero->nombreCarrera,
                    'yaInscrito' => $yaInscrito,
                    'grupoSeleccionado' => [
                        'idGrupo' => $grupo->idGrupo,
                        'nombreGrupo' => $grupo->nombreGrupo,
                        'codigoGrupo' => $grupo->codigoGrupo,
                        'paralelo' => $grupo->paralelo,
                        'turno' => $grupo->turno,
                        'gestion' => $grupo->gestion,
                        'cupos' => $grupo->cupos,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'materias' => $materias,
        ]);
    }

    public function inscribirSemestreUno(Request $request, $idUsuario)
    {
        $request->validate([
            'idCarrera' => ['nullable', 'integer', 'exists:Carrera,idCarrera'],
        ]);

        $idCarrera = $request->input('idCarrera');

        return DB::transaction(function () use ($idUsuario, $idCarrera) {
            $estudiante = User::findOrFail($idUsuario);

            $query = DB::table('Materia as m')
                ->join('CarreraMateria as cm', 'cm.idMateria', '=', 'm.idMateria')
                ->join('Carrera as c', 'c.idCarrera', '=', 'cm.idCarrera')
                ->join('GrupoMateriaDocente as gmd', 'gmd.idMateria', '=', 'm.idMateria')
                ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
                ->where('m.semestre', 1)
                ->where('m.estado', 'activo')
                ->where('g.estado', 'activo');

            if ($idCarrera) {
                $query->where('c.idCarrera', $idCarrera);
            } else {
                $query->whereIn('c.idCarrera', function ($sub) use ($idUsuario) {
                    $sub->select('idCarrera')
                        ->from('CarreraUsuario')
                        ->where('idUsuario', $idUsuario);
                });
            }

            $materiasConGrupo = $query
                ->select(
                    'm.idMateria',
                    'm.nombreMateria',
                    'g.idGrupo'
                )
                ->orderBy('m.nombreMateria')
                ->orderBy('g.idGrupo')
                ->get()
                ->groupBy('idMateria')
                ->map(fn ($items) => $items->first())
                ->values();

            if ($materiasConGrupo->isEmpty()) {
                return response()->json([
                    'message' => 'No hay materias del semestre 1 con grupo activo para este estudiante.',
                ], 422);
            }

            $inscritas = [];
            $omitidas = [];
            $sinCupos = [];
            $cuposDescontados = 0;

            foreach ($materiasConGrupo as $item) {
                $yaInscritoEnMateria = DB::table('Inscripcion as i')
                    ->join('GrupoMateriaDocente as gmd', 'gmd.idGrupo', '=', 'i.idGrupo')
                    ->where('i.idUsuario', $idUsuario)
                    ->where('gmd.idMateria', $item->idMateria)
                    ->exists();

                if ($yaInscritoEnMateria) {
                    $omitidas[] = $item->nombreMateria;
                    continue;
                }

                $grupo = DB::table('Grupo')
                    ->where('idGrupo', $item->idGrupo)
                    ->where('estado', 'activo')
                    ->lockForUpdate()
                    ->first();

                if (!$grupo) {
                    $omitidas[] = $item->nombreMateria . ' (grupo inactivo)';
                    continue;
                }

                if ((int) $grupo->cupos <= 0) {
                    $sinCupos[] = $item->nombreMateria;
                    continue;
                }

                DB::table('Inscripcion')->insert([
                    'idUsuario' => $estudiante->id,
                    'idGrupo' => $item->idGrupo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('Grupo')
                    ->where('idGrupo', $item->idGrupo)
                    ->decrement('cupos');

                $cuposDescontados++;
                $inscritas[] = $item->nombreMateria;
            }

            if (count($inscritas) === 0 && count($sinCupos) > 0) {
                return response()->json([
                    'message' => 'No se pudo inscribir. No hay cupos disponibles.',
                    'inscritas' => $inscritas,
                    'omitidas' => $omitidas,
                    'sinCupos' => $sinCupos,
                    'cuposDescontados' => $cuposDescontados,
                ], 422);
            }

            return response()->json([
                'message' => 'Inscripción automática realizada.',
                'inscritas' => $inscritas,
                'omitidas' => $omitidas,
                'sinCupos' => $sinCupos,
                'cuposDescontados' => $cuposDescontados,
            ]);
        });
    }

    public function actualizarEstudiante(Request $request, $idUsuario)
    {
        $estudiante = User::findOrFail($idUsuario);

        $validated = $request->validate([
            'apellidoPaterno' => ['required', 'string', 'max:50'],
            'apellidoMaterno' => ['nullable', 'string', 'max:50'],
            'nombres' => ['required', 'string', 'max:50'],
            'genero' => ['required', Rule::in(['MASCULINO', 'FEMENINO', 'Masculino', 'Femenino'])],
            'ci' => ['required', 'string', 'max:50', Rule::unique('user', 'ci')->ignore($estudiante->id)],
            'expedido' => ['nullable', 'string', 'max:10'],
            'fecha_nac' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:50', Rule::unique('user', 'email')->ignore($estudiante->id)],
            'telefono' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:50'],
            'estado' => ['nullable', 'string', 'max:20'],
        ]);

        $estudiante->update($validated);

        return response()->json([
            'message' => 'Estudiante actualizado correctamente.',
            'estudiante' => $estudiante->fresh(),
        ]);
    }
}