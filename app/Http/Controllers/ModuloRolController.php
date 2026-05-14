<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuloRolController extends Controller
{

    public function index(): JsonResponse
    {
        $asignaciones = DB::table('modulo_rol')
            ->join('rol',    'modulo_rol.id_rol',    '=', 'rol.id')
            ->join('modulo', 'modulo_rol.id_modulo', '=', 'modulo.id')
            ->select(
                'modulo_rol.id',
                'modulo_rol.id_rol',
                'modulo_rol.id_modulo',
                'rol.rol           as nombre_rol',
                'modulo.modulo     as nombre_modulo',
                'modulo.icono      as icono_modulo',
                'modulo.descripcion as descripcion_modulo',
                'modulo_rol.created_at'
            )
            ->orderBy('rol.rol')
            ->orderBy('modulo.modulo')
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
                'id_rol'    => 'required|integer|exists:rol,id',
                'id_modulo' => 'required|integer|exists:modulo,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);
        }

        $existe = DB::table('modulo_rol')
            ->where('id_rol',    $validated['id_rol'])
            ->where('id_modulo', $validated['id_modulo'])
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Este módulo ya está asignado a ese rol.',
            ], 409);
        }

        $id = DB::table('modulo_rol')->insertGetId([
            'id_rol'     => $validated['id_rol'],
            'id_modulo'  => $validated['id_modulo'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $registro = DB::table('modulo_rol')
            ->join('rol',    'modulo_rol.id_rol',    '=', 'rol.id')
            ->join('modulo', 'modulo_rol.id_modulo', '=', 'modulo.id')
            ->select(
                'modulo_rol.id',
                'modulo_rol.id_rol',
                'modulo_rol.id_modulo',
                'rol.rol           as nombre_rol',
                'modulo.modulo     as nombre_modulo',
                'modulo.icono      as icono_modulo',
            )
            ->where('modulo_rol.id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Módulo asignado al rol correctamente.',
            'data'    => $registro,
        ], 201);
    }


    public function destroy(int $id): JsonResponse
    {
        $existe = DB::table('modulo_rol')->where('id', $id)->exists();

        if (! $existe) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada.',
            ], 404);
        }

        DB::table('modulo_rol')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asignación eliminada correctamente.',
        ]);
    }

}