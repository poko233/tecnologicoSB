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

        $roles = DB::table('user_rol')
            ->where('id_user', $user->id)
            ->pluck('id_rol'); 

        if ($roles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene roles asignados.',
            ], 403);
        }

        $modulos = DB::table('modulo_rol')
            ->join('modulo', 'modulo_rol.id_modulo', '=', 'modulo.id')
            ->select(
                'modulo.id',
                'modulo.modulo      as nombre',
                'modulo.descripcion',
                'modulo.icono',
            )
            ->whereIn('modulo_rol.id_rol', $roles) 
            ->distinct()                          
            ->orderBy('modulo.modulo')
            ->get();

        $modulosConFormularios = $modulos->map(function ($modulo) {
            $formularios = DB::table('formulario_modulo')
                ->join('formulario', 'formulario_modulo.id_formulario', '=', 'formulario.id')
                ->select(
                    'formulario.id',
                    'formulario.formulario as nombre',
                    'formulario.ruta',
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
            'roles'   => $roles,        
            'modulos' => $modulosConFormularios,
        ]);
    }
}