<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarreraRequest;
use App\Http\Requests\UpdateCarreraRequest;
use App\Http\Resources\CarreraResource;
use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarreraController extends Controller
{
    // ─── Público (solo requiere auth:sanctum) ───────────────────────────────

    /**
     * GET /carreras
     * Lista carreras activas (cualquier usuario autenticado puede verlas).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $carreras = Carrera::with('area')
            ->where('estadoCarrera', 'activo')
            ->orderBy('nombreCarrera')
            ->get();

        return CarreraResource::collection($carreras);
    }

    /**
     * GET /carreras/{carrera}
     */
    public function show(string $idCarrera): CarreraResource
    {
        $carrera = Carrera::with(['area', 'materias'])->findOrFail($idCarrera);

        return new CarreraResource($carrera);
    }

    /**
     * GET /carreras/{idCarrera}/materias
     */
    public function materias(string $idCarrera): JsonResponse
    {
        $materias = Materia::select('Materia.*')
            ->join('CarreraMateria', 'CarreraMateria.idMateria', '=', 'Materia.idMateria')
            ->where('CarreraMateria.idCarrera', $idCarrera)
            ->where('Materia.estado', 'activo')
            ->orderBy('Materia.semestre')
            ->orderBy('Materia.nombreMateria')
            ->get();

        return response()->json(['materias' => $materias]);
    }

    /**
     * GET /materias/{idMateria}/grupos
     */
    public function gruposPorMateria(string $idMateria): JsonResponse
    {
        $grupos = Grupo::select('Grupo.*')
            ->join('GrupoMateriaDocente', 'GrupoMateriaDocente.idGrupo', '=', 'Grupo.idGrupo')
            ->where('GrupoMateriaDocente.idMateria', $idMateria)
            ->where('Grupo.estado', 'activo')
            ->orderBy('Grupo.turno')
            ->orderBy('Grupo.nombre')
            ->get();

        return response()->json(['grupos' => $grupos]);
    }

    // ─── Solo Admin (protegidos por middleware 'admin') ─────────────────────

    /**
     * POST /admin/carreras
     */
    public function store(StoreCarreraRequest $request): CarreraResource
    {
        $carrera = Carrera::create($request->validated());

        return (new CarreraResource($carrera->load('area')))
            ->additional(['message' => 'Carrera creada correctamente.']);
    }

    /**
     * PUT /admin/carreras/{carrera}
     */
    public function update(UpdateCarreraRequest $request, string $idCarrera): CarreraResource
    {
        $carrera = Carrera::findOrFail($idCarrera);
        $carrera->update($request->validated());

        return (new CarreraResource($carrera->load('area')))
            ->additional(['message' => 'Carrera actualizada correctamente.']);
    }

    /**
     * DELETE /admin/carreras/{carrera}
     * Soft-delete lógico: pone estado en inactivo.
     */
    public function destroy(string $idCarrera): JsonResponse
    {
        $carrera = Carrera::findOrFail($idCarrera);
        $carrera->update(['estadoCarrera' => 'inactivo']);

        return response()->json(['message' => 'Carrera desactivada correctamente.']);
    }
}