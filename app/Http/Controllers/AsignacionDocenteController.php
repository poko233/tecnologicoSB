<?php

namespace App\Http\Controllers;

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
        $materias = Materia::orderBy('semestre')
            ->orderBy('nombreMateria')
            ->get();

        $grupos = Grupo::orderBy('idGrupo')->get();

        $docentes = Docente::with('usuario')
            ->where('estadoDocente', 'activo')
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
            ->first();

        if (!$docente) {
            return response()->json([
                'message' => 'El docente seleccionado está inactivo o no existe.',
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