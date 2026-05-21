<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use App\Http\Resources\AreaResource;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AreaController extends Controller
{
    /**
     * GET /areas
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $areas = Area::when($request->all, function ($query) {
                return $query;
            }, function ($query) {
                return $query->activas();
            })
            ->orderBy('nombre')
            ->get();

        return AreaResource::collection($areas);
    }

    /**
     * GET /areas/{area}
     */
    public function show(string $idArea): AreaResource
    {
        $area = Area::with('carreras')->findOrFail($idArea);
        return new AreaResource($area);
    }

    /**
     * POST /areas
     */
    public function store(StoreAreaRequest $request): AreaResource
    {
        $area = Area::create($request->validated());

        return (new AreaResource($area))
            ->additional(['message' => 'Área creada correctamente.']);
    }

    /**
     * PUT /areas/{area}
     */
    public function update(UpdateAreaRequest $request, string $idArea): AreaResource
    {
        $area = Area::findOrFail($idArea);
        $area->update($request->validated());

        return (new AreaResource($area))
            ->additional(['message' => 'Área actualizada correctamente.']);
    }

    /**
     * DELETE /areas/{area}
     */
    public function destroy(string $idArea): JsonResponse
    {
        $area = Area::findOrFail($idArea);
        $area->update(['estado' => 'inactivo']);

        return response()->json(['message' => 'Área desactivada correctamente.']);
    }
}