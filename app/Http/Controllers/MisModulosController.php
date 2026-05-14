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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $rol = DB::table('user_rol')
            ->where('id_user', $user->id)
            ->first();

        if (! $rol) {
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

        return response()->json([
            'success' => true,
            'id_rol'  => $idRol,
            'modulos' => $modulos,
        ]);
    }
}