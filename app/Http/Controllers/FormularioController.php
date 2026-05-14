<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Formulario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FormularioController extends Controller
{
    public function index(): JsonResponse
    {
        $formularios = Formulario::with(['modulos'])->get();

        return response()->json([
            'success' => true,
            'data'    => $formularios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'formulario'  => 'required|string|max:100|unique:formulario,formulario',
                'descripcion' => 'nullable|string|max:255',
                'ruta'        => 'nullable|string|max:255',
                'componente'  => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        $formulario = Formulario::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Formulario creado correctamente.',
            'data'    => $formulario,
        ], 201);
    }


    public function show(int $id): JsonResponse
    {
        $formulario = Formulario::with(['modulos'])->find($id);

        if (! $formulario) {
            return response()->json([
                'success' => false,
                'message' => 'Formulario no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $formulario,
        ]);
    }

    
    public function update(Request $request, int $id): JsonResponse
    {
        $formulario = Formulario::find($id);

        if (! $formulario) {
            return response()->json([
                'success' => false,
                'message' => 'Formulario no encontrado.',
            ], 404);
        }

        try {
            $validated = $request->validate([
                'formulario'  => 'sometimes|required|string|max:100|unique:formulario,formulario,' . $id,
                'descripcion' => 'nullable|string|max:255',
                'ruta'        => 'nullable|string|max:255',
                'componente'  => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        $formulario->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Formulario actualizado correctamente.',
            'data'    => $formulario,
        ]);
    }

    
    public function destroy(int $id): JsonResponse
    {
        $formulario = Formulario::find($id);

        if (! $formulario) {
            return response()->json([
                'success' => false,
                'message' => 'Formulario no encontrado.',
            ], 404);
        }

        $formulario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Formulario eliminado correctamente.',
        ]);
    }
}