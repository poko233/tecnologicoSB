<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermisoResource;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestiona permisos CRUD por Rol + Formulario.
 *
 * Rutas:
 *   GET    /api/roles/{rol}/permisos            → todos los permisos del rol
 *   POST   /api/roles/{rol}/permisos            → crear/actualizar un permiso
 *   PUT    /api/roles/{rol}/permisos/{permiso}  → actualizar flags individuales
 *   POST   /api/roles/{rol}/permisos/sync       → reemplazar todos los permisos del rol
 *   DELETE /api/roles/{rol}/permisos/{permiso}  → eliminar (revocar todos los flags)
 */
class PermisoController extends Controller
{
    /** Lista todos los permisos del rol con su formulario cargado. */
    public function index(Rol $rol): JsonResponse
    {
        $permisos = $rol->permisos()->with('formulario')->get();

        return PermisoResource::collection($permisos)->response();
    }

    /**
     * Crea o actualiza el permiso de un rol sobre un formulario.
     * Usa updateOrCreate para ser idempotente.
     */
    public function store(Request $request, Rol $rol): JsonResponse
    {
        $data = $request->validate([
            'id_formulario' => 'required|exists:formulario,id',
            'ver'           => 'boolean',
            'crear'         => 'boolean',
            'editar'        => 'boolean',
            'eliminar'      => 'boolean',
        ]);

        $permiso = Permiso::updateOrCreate(
            ['id_rol' => $rol->id, 'id_formulario' => $data['id_formulario']],
            [
                'ver'      => $data['ver']      ?? false,
                'crear'    => $data['crear']    ?? false,
                'editar'   => $data['editar']   ?? false,
                'eliminar' => $data['eliminar'] ?? false,
            ]
        );

        return (new PermisoResource($permiso->load('formulario')))
            ->response()
            ->setStatusCode(201);
    }

    /** Actualiza flags de un permiso existente. */
    public function update(Request $request, Rol $rol, Permiso $permiso): JsonResponse
    {
        $this->authorizePermiso($rol, $permiso);

        $data = $request->validate([
            'ver'      => 'boolean',
            'crear'    => 'boolean',
            'editar'   => 'boolean',
            'eliminar' => 'boolean',
        ]);

        $permiso->update($data);

        return (new PermisoResource($permiso->load('formulario')))->response();
    }

    /**
     * Sincroniza todos los permisos del rol.
     * Reemplaza la matriz completa de permisos.
     *
     * Body: { "permisos": [{ "id_formulario": 1, "ver": true, "crear": false, ... }] }
     */
    public function sync(Request $request, Rol $rol): JsonResponse
    {
        $request->validate([
            'permisos'                  => 'required|array',
            'permisos.*.id_formulario'  => 'required|exists:formulario,id',
            'permisos.*.ver'            => 'boolean',
            'permisos.*.crear'          => 'boolean',
            'permisos.*.editar'         => 'boolean',
            'permisos.*.eliminar'       => 'boolean',
        ]);

        // Eliminar permisos actuales y recrear
        Permiso::where('id_rol', $rol->id)->delete();

        $nuevos = collect($request->permisos)->map(fn($p) => Permiso::create([
            'id_rol'        => $rol->id,
            'id_formulario' => $p['id_formulario'],
            'ver'           => $p['ver']      ?? false,
            'crear'         => $p['crear']    ?? false,
            'editar'        => $p['editar']   ?? false,
            'eliminar'      => $p['eliminar'] ?? false,
        ]));

        return response()->json([
            'message'  => 'Permisos sincronizados',
            'permisos' => PermisoResource::collection($nuevos->load('formulario')),
        ]);
    }

    /** Revoca todos los flags (elimina la fila). */
    public function destroy(Rol $rol, Permiso $permiso): JsonResponse
    {
        $this->authorizePermiso($rol, $permiso);
        $permiso->delete();

        return response()->json(['message' => 'Permiso revocado']);
    }

    private function authorizePermiso(Rol $rol, Permiso $permiso): void
    {
        abort_if($permiso->id_rol !== $rol->id, 404, 'Permiso no pertenece al rol');
    }
}
