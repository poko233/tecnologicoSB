<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica que el usuario autenticado tenga
 * al menos uno de los roles indicados (por nombre, no por ID).
 *
 * Uso en rutas:  ->middleware('role:Docente')
 *                ->middleware('role:Administrador,Portero,Seguridad')
 */
class CheckRolNombre
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $rolesUsuario = $user->roles()->pluck('rol')->toArray();

        if (empty(array_intersect($roles, $rolesUsuario))) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
