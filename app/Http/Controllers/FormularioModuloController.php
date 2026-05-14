<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use App\Models\Formulario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormularioModuloController extends Controller
{
    
    public function index(): JsonResponse
    {
        $asignaciones = DB::table('formulario_modulo')
            ->join('modulo',     'formulario_modulo.id_modulo',     '=', 'modulo.id')
            ->join('formulario', 'formulario_modulo.id_formulario', '=', 'formulario.id')
            ->select(
                'formulario_modulo.id',
                'formulario_modulo.id_modulo',
                'formulario_modulo.id_formulario',
                'modulo.modulo      as nombre_modulo',
                'modulo.icono       as icono_modulo',
                'formulario.formulario as nombre_formulario',
                'formulario.ruta    as ruta_formulario',
                'formulario_modulo.created_at'
            )
            ->orderBy('modulo.modulo')
            ->orderBy('formulario.formulario')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $asignaciones,
        ]);
    }

    
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_modulo'     => 'required|integer|exists:modulo,id',
                'id_formulario' => 'required|integer|exists:formulario,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        $existe = DB::table('formulario_modulo')
            ->where('id_modulo',     $validated['id_modulo'])
            ->where('id_formulario', $validated['id_formulario'])
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Esta asignación ya existe.',
            ], 409);
        }

        $id = DB::table('formulario_modulo')->insertGetId([
            'id_modulo'     => $validated['id_modulo'],
            'id_formulario' => $validated['id_formulario'],
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $registro = DB::table('formulario_modulo')
            ->join('modulo',     'formulario_modulo.id_modulo',     '=', 'modulo.id')
            ->join('formulario', 'formulario_modulo.id_formulario', '=', 'formulario.id')
            ->select(
                'formulario_modulo.id',
                'formulario_modulo.id_modulo',
                'formulario_modulo.id_formulario',
                'modulo.modulo         as nombre_modulo',
                'modulo.icono          as icono_modulo',
                'formulario.formulario as nombre_formulario',
                'formulario.ruta       as ruta_formulario',
                'formulario_modulo.created_at'
            )
            ->where('formulario_modulo.id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Asignación creada correctamente.',
            'data'    => $registro,
        ], 201);
    }

    
    public function destroy(int $id): JsonResponse
    {
        $existe = DB::table('formulario_modulo')->where('id', $id)->exists();

        if (! $existe) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada.',
            ], 404);
        }

        DB::table('formulario_modulo')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asignación eliminada correctamente.',
        ]);
    }

    
    public function porModulo(int $idModulo): JsonResponse
    {
        $modulo = Modulo::find($idModulo);

        if (! $modulo) {
            return response()->json(['success' => false, 'message' => 'Módulo no encontrado.'], 404);
        }

        $formularios = DB::table('formulario_modulo')
            ->join('formulario', 'formulario_modulo.id_formulario', '=', 'formulario.id')
            ->select(
                'formulario_modulo.id',
                'formulario_modulo.id_formulario',
                'formulario.formulario as nombre_formulario',
                'formulario.ruta       as ruta_formulario',
                'formulario_modulo.created_at'
            )
            ->where('formulario_modulo.id_modulo', $idModulo)
            ->orderBy('formulario.formulario')
            ->get();

        return response()->json([
            'success' => true,
            'modulo'  => $modulo->modulo,
            'data'    => $formularios,
        ]);
    }
}