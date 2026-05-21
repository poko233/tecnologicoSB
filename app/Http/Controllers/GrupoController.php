<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGrupoRequest;
use App\Http\Requests\UpdateGrupoRequest;
use App\Http\Resources\GrupoResource;
use App\Models\Grupo;
use App\Models\Horario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class GrupoController extends Controller
{
    /**
     * GET /grupos
     * Lista grupos activos con sus horarios.
     */
    public function index(): AnonymousResourceCollection
    {
        $grupos = Grupo::with('horarios')
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();

        return GrupoResource::collection($grupos);
    }

    /**
     * GET /grupos/{id}
     */
    public function show(string $id): GrupoResource
    {
        $grupo = Grupo::with('horarios')->findOrFail($id);

        return new GrupoResource($grupo);
    }

    /**
     * POST /grupos
     */
    public function store(StoreGrupoRequest $request): GrupoResource
    {
        return DB::transaction(function () use ($request) {
            $grupo = Grupo::create($request->validated());

            if ($request->has('horarios')) {
                $horariosIds = [];
                foreach ($request->input('horarios') as $horarioData) {
                    $horario = Horario::create($horarioData);
                    $horariosIds[] = $horario->idHorario;
                }
                $grupo->horarios()->sync($horariosIds);
            }

            return (new GrupoResource($grupo->load('horarios')))
                ->additional(['message' => 'Grupo creado correctamente.']);
        });
    }

    /**
     * PUT /grupos/{id}
     */
    public function update(UpdateGrupoRequest $request, string $id): GrupoResource
    {
        return DB::transaction(function () use ($request, $id) {
            $grupo = Grupo::findOrFail($id);
            $grupo->update($request->validated());

            if ($request->has('horarios')) {
                $horariosIds = [];
                foreach ($request->input('horarios') as $horarioData) {
                    $horario = Horario::create($horarioData);
                    $horariosIds[] = $horario->idHorario;
                }
                $grupo->horarios()->sync($horariosIds);
            }

            return (new GrupoResource($grupo->load('horarios')))
                ->additional(['message' => 'Grupo actualizado correctamente.']);
        });
    }

    /**
     * DELETE /grupos/{id}
     * Soft-delete lógico: cambia estado a 'inactivo'.
     */
    public function destroy(string $id): JsonResponse
    {
        $grupo = Grupo::findOrFail($id);
        $grupo->update(['estado' => 'inactivo']);

        return response()->json(['message' => 'Grupo desactivado correctamente.']);
    }
}
