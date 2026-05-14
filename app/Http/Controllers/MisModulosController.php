<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MisModulosController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $rol = DB::table('user_rol')
            ->where('id_user', $user->id)
            ->first();

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene un rol asignado.',
            ], 403);
        }

        $idRol = $rol->id_rol;

        $modulos = DB::table('modulo_rol')
            ->join('modulo', 'modulo_rol.id_modulo', '=', 'modulo.id')
            ->select(
                'modulo.id',
                'modulo.modulo      as nombre',
                'modulo.descripcion',
                'modulo.icono',
            )
            ->where('modulo_rol.id_rol', $idRol)
            ->orderBy('modulo.modulo')
            ->get();

        $modulosConFormularios = $modulos->map(function ($modulo) {
            $formularios = DB::table('formulario_modulo')
                ->join('formulario', 'formulario_modulo.id_formulario', '=', 'formulario.id')
                ->select(
                    'formulario.id',
                    'formulario.formulario as nombre',
                    'formulario.ruta',
                    'formulario.icono',
                    'formulario.descripcion',
                )
                ->where('formulario_modulo.id_modulo', $modulo->id)
                ->orderBy('formulario.formulario')
                ->get();

            return [
                'id'          => $modulo->id,
                'nombre'      => $modulo->nombre,
                'descripcion' => $modulo->descripcion,
                'icono'       => $modulo->icono,
                'formularios' => $formularios,
            ];
        });

        return response()->json([
            'success' => true,
            'id_rol'  => $idRol,
            'modulos' => $modulosConFormularios,
        ]);
    }
}