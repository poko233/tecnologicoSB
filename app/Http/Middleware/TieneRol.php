<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TieneRol
{
    private const ROLES_PERMITIDOS = [1, 2]; 

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $tieneRol = DB::table('user_rol')
            ->where('id_user', $user->id)
            ->whereIn('id_rol', self::ROLES_PERMITIDOS)
            ->exists(); 

        if (!$tieneRol) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}