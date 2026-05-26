<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMateriaRequest;
use App\Http\Requests\UpdateMateriaRequest;
use App\Http\Resources\MateriaResource;
use App\Models\Materia;
use App\Models\Carrera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MateriaController extends Controller
{
    /**
     * GET /materias
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $materias = Materia::with(['carreras' => function($query) {
                $query->where('estadoCarrera', 'activo');
            }])
            ->when($request->all, function ($query) {
                return $query;
            }, function ($query) {
                return $query->activas();
            })
            ->orderBy('nombreMateria')
            ->get();

        return MateriaResource::collection($materias);
    }

    /**
     * GET /materias/{materia}
     */
    public function show(string $idMateria): MateriaResource
    {
        $materia = Materia::with(['carreras', 'prerequisito'])->findOrFail($idMateria);
        return new MateriaResource($materia);
    }

    /**
     * POST /materias
     */
    public function store(StoreMateriaRequest $request): MateriaResource
    {
        return DB::transaction(function () use ($request) {
            $materia = Materia::create($request->validated());
            
            // Relacionar con la carrera
            if ($request->has('idCarrera')) {
                $materia->carreras()->attach($request->idCarrera);
            }

            return (new MateriaResource($materia->load('carreras')))
                ->additional(['message' => 'Materia creada y vinculada correctamente.']);
        });
    }

    /**
     * PUT /materias/{materia}
     */
    public function update(UpdateMateriaRequest $request, string $idMateria): MateriaResource
    {
        $materia = Materia::findOrFail($idMateria);
        
        return DB::transaction(function () use ($request, $materia) {
            $materia->update($request->validated());

            if ($request->has('idCarrera')) {
                $materia->carreras()->sync([$request->idCarrera]);
            }

            return (new MateriaResource($materia->load('carreras')))
                ->additional(['message' => 'Materia actualizada correctamente.']);
        });
    }

    /**
     * DELETE /materias/{materia}
     */
    public function destroy(string $idMateria): JsonResponse
    {
        $materia = Materia::findOrFail($idMateria);
        $materia->update(['estado' => 'inactivo']);

        return response()->json(['message' => 'Materia desactivada correctamente.']);
    }
    public function gruposPorMateria(string $idMateria): JsonResponse
{
    $gruposBase = DB::table('GrupoMateriaDocente as gmd')
        ->join('Grupo as g', 'g.idGrupo', '=', 'gmd.idGrupo')
        ->select(
            'g.idGrupo',
            'g.nombre as nombreGrupo',
            'g.nombre',
            'g.codigo',
            'g.paralelo',
            'g.turno',
            'g.gestion',
            'g.cupos as capacidad',
            'g.cupos',
            'g.tipo',
            'g.estado',
            'gmd.idMateria',
            'gmd.idDocente'
        )
        ->where('gmd.idMateria', $idMateria)
        ->where('g.estado', 'activo')
        ->distinct()
        ->orderBy('g.idGrupo')
        ->get();

    $idsGrupos = $gruposBase->pluck('idGrupo')->unique()->values();

    $horarios = DB::table('GrupoHorario as gh')
        ->join('Horario as h', 'h.idHorario', '=', 'gh.idHorario')
        ->select(
            'gh.idGrupo',
            'h.idHorario',
            'h.dia',
            'h.horaInicio',
            'h.horaFin'
        )
        ->whereIn('gh.idGrupo', $idsGrupos)
        ->orderByRaw("
            CASE h.dia
                WHEN 'Lunes' THEN 1
                WHEN 'Martes' THEN 2
                WHEN 'Miércoles' THEN 3
                WHEN 'Miercoles' THEN 3
                WHEN 'Jueves' THEN 4
                WHEN 'Viernes' THEN 5
                WHEN 'Sábado' THEN 6
                WHEN 'Sabado' THEN 6
                WHEN 'Domingo' THEN 7
                ELSE 8
            END
        ")
        ->orderBy('h.horaInicio')
        ->get()
        ->groupBy('idGrupo');

    $grupos = $gruposBase->map(function ($grupo) use ($horarios) {
        $grupo->horarios = $horarios->get($grupo->idGrupo, collect())
            ->map(function ($horario) {
                return [
                    'idHorario' => $horario->idHorario,
                    'dia' => $horario->dia,
                    'horaInicio' => substr($horario->horaInicio, 0, 5),
                    'horaFin' => substr($horario->horaFin, 0, 5),
                ];
            })
            ->values();

        return $grupo;
    });

    return response()->json([
        'grupos' => $grupos,
    ]);
}
}