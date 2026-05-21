<?php

namespace App\Http\Controllers;

use App\Http\Resources\HorarioResource;
use App\Models\Horario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HorarioController extends Controller
{
    /**
     * GET /horarios
     */
    public function index(): AnonymousResourceCollection
    {
        $horarios = Horario::orderBy('dia')->orderBy('horaInicio')->get();
        return HorarioResource::collection($horarios);
    }

    /**
     * POST /horarios
     */
    public function store(Request $request): HorarioResource
    {
        $validated = $request->validate([
            'horaInicio' => 'required|date_format:H:i',
            'horaFin'    => 'required|date_format:H:i|after:horaInicio',
            'dia'        => 'required|string|max:50',
        ]);

        $horario = Horario::create($validated);

        return new HorarioResource($horario);
    }

    /**
     * DELETE /horarios/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $horario = Horario::findOrFail($id);
        $horario->delete();

        return response()->json(['message' => 'Horario eliminado correctamente.']);
    }
}
