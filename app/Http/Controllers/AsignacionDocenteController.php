<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\GrupoMateriaDocente;
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignacionDocenteController extends Controller
{
    public function index()
    {
        $carreras = Carrera::orderBy('nombreCarrera')->get();

        $materias = Materia::select('Materia.*', 'CarreraMateria.idCarrera')
            ->join('CarreraMateria', 'CarreraMateria.idMateria', '=', 'Materia.idMateria')
            ->orderBy('CarreraMateria.idCarrera')
            ->orderBy('Materia.semestre')
            ->orderBy('Materia.nombreMateria')
            ->get();

        $grupos = Grupo::orderBy('idGrupo')->get();

        $docentes = Docente::with(['usuario.roles'])
            ->where('estadoDocente', 'activo')
            ->whereHas('usuario.roles', function ($query) {
                $query->where('rol.id', 3);
            })
            ->orderBy('profesion')
            ->get();

        $asignaciones = GrupoMateriaDocente::with([
            'materia',
            'grupo',
            'docente.usuario',
        ])
            ->orderBy('idMateria')
            ->orderBy('idGrupo')
            ->get();

        return response()->json([
            'carreras' => $carreras,
            'materias' => $materias,
            'grupos' => $grupos,
            'docentes' => $docentes,
            'asignaciones' => $asignaciones,
        ]);
    }

    public function guardar(Request $request)
    {
        $validated = $request->validate([
            'idMateria' => 'required|integer|exists:Materia,idMateria',
            'idDocente' => 'required|integer|exists:Docente,idDocente',
            'grupos' => 'required|array|min:1',
            'grupos.*' => 'required|integer|exists:Grupo,idGrupo',
        ]);

        $docente = Docente::where('idDocente', $validated['idDocente'])
            ->where('estadoDocente', 'activo')
            ->whereHas('usuario.roles', function ($query) {
                $query->where('rol.id', 3);
            })
            ->first();

        if (!$docente) {
            return response()->json([
                'message' => 'El docente seleccionado está inactivo, no existe o no tiene el rol Docente.',
            ], 422);
        }

        DB::transaction(function () use ($validated) {
            GrupoMateriaDocente::where('idMateria', $validated['idMateria'])->delete();

            foreach ($validated['grupos'] as $idGrupo) {
                GrupoMateriaDocente::create([
                    'idMateria' => $validated['idMateria'],
                    'idDocente' => $validated['idDocente'],
                    'idGrupo' => $idGrupo,
                ]);
            }
        });

        return response()->json([
            'message' => 'Asignación guardada correctamente',
        ]);
    }

    public function eliminarPorMateria($idMateria)
    {
        GrupoMateriaDocente::where('idMateria', $idMateria)->delete();

        return response()->json([
            'message' => 'Asignación eliminada correctamente',
        ]);
    }
}