<?php

namespace App\Http\Controllers;

use App\Services\SidebarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/sidebar
 *
 * Devuelve el menú lateral dinámico para el usuario autenticado,
 * filtrado según sus permisos de tipo 'ver=true' en cada formulario.
 *
 * Este endpoint alimenta directamente el sidebar del frontend.
 * La sección "Configuraciones" con sus Tabs (Roles, Módulos,
 * Formularios, Permisos) es un módulo más en la BD con sidebar=true.
 */
class SidebarController extends Controller
{
    public function __construct(private SidebarService $sidebarService) {}

    public function index(Request $request): JsonResponse
    {
        $sidebar = $this->sidebarService->getSidebarForUser($request->user());

        return response()->json(['data' => $sidebar]);
    }
}
