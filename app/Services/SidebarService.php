<?php

namespace App\Services;

use App\Models\Modulo;
use App\Models\User;

class SidebarService
{
    /**
     * Genera el sidebar dinámico para el usuario autenticado.
     *
     * Flujo:
     *  1. Obtiene los ids de roles del usuario.
     *  2. Consulta los módulos con sidebar=true, ordenados por 'orden'.
     *  3. Para cada módulo, filtra los formularios donde el rol tiene ver=true.
     *  4. Descarta módulos sin formularios visibles.
     *
     * Estructura de respuesta:
     *  [
     *    { id, modulo, icono, orden, formularios: [
     *        { id, formulario, ruta, componente, icono, orden, permisos: {crear, editar, eliminar} }
     *    ]}
     *  ]
     */
    public function getSidebarForUser(User $user): array
    {
        // IDs de roles del usuario autenticado
        $rolIds = $user->roles()->pluck('rol.id');

        $modulos = Modulo::where('sidebar', true)
            ->orderBy('orden')
            ->with([
                // Formularios con permisos del rol actual
                'formularios' => function ($query) use ($rolIds) {
                    $query->orderBy('orden')
                          ->whereHas('permisos', function ($q) use ($rolIds) {
                              $q->whereIn('id_rol', $rolIds)->where('ver', true);
                          })
                          ->with(['permisos' => function ($q) use ($rolIds) {
                              $q->whereIn('id_rol', $rolIds);
                          }]);
                },
            ])
            ->get();

        // Filtrar módulos sin formularios visibles
        return $modulos
            ->filter(fn($m) => $m->formularios->isNotEmpty())
            ->map(fn($m) => [
                'id'          => $m->id,
                'modulo'      => $m->modulo,
                'icono'       => $m->icono,
                'orden'       => $m->orden,
                'formularios' => $m->formularios->map(function ($f) {
                    // Consolidar permisos (OR entre roles si el usuario tiene varios)
                    $crear    = $f->permisos->contains('crear', true);
                    $editar   = $f->permisos->contains('editar', true);
                    $eliminar = $f->permisos->contains('eliminar', true);

                    return [
                        'id'          => $f->id,
                        'formulario'  => $f->formulario,
                        'ruta'        => $f->ruta,
                        'componente'  => $f->componente,
                        'icono'       => $f->icono,
                        'orden'       => $f->orden,
                        'permisos'    => compact('crear', 'editar', 'eliminar'),
                    ];
                })->values(),
            ])
            ->values()
            ->toArray();
    }
}
