<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    public function index(Request $request)
    {
        $query = Carrera::where('estadoCarrera', 'activo');

        return response()->json([
            'carreras' => $query->orderBy('nombreCarrera')->get()
        ]);
    }

    public function materias(string $idCarrera)
    {
        $materias = Materia::select('Materia.*')
            ->join('CarreraMateria', 'CarreraMateria.idMateria', '=', 'Materia.idMateria')
            ->where('CarreraMateria.idCarrera', $idCarrera)
            ->where('Materia.estado', 'activo')
            ->orderBy('Materia.semestre')
            ->orderBy('Materia.nombreMateria')
            ->get();

        return response()->json([
            'materias' => $materias
        ]);
    }

    public function gruposPorMateria(string $idMateria)
    {
        $grupos = Grupo::select('Grupo.*')
            ->join('GrupoMateriaDocente', 'GrupoMateriaDocente.idGrupo', '=', 'Grupo.idGrupo')
            ->where('GrupoMateriaDocente.idMateria', $idMateria)
            ->where('Grupo.estado', 'activo')
            ->orderBy('Grupo.turno')
            ->orderBy('Grupo.nombre')
            ->get();

        return response()->json([
            'grupos' => $grupos
        ]);
    }
}