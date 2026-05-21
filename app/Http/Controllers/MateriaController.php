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
}