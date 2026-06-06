<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ElementoCompetencia;
use App\Models\GrupoMateriaDocente;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ElementoCompetenciaService
{
    /**
     * Lista todos los elementos de competencia (activos e inactivos)
     * de un grupo-materia-docente, verificando que el usuario tenga acceso.
     */
    public function listar(int $idGrupoMateriaDocente, int $userId): Collection
    {
        $gmd = GrupoMateriaDocente::findOrFail($idGrupoMateriaDocente);
        $this->verificarAccesoDocenteOAdmin($gmd, $userId);

        return ElementoCompetencia::where('id_grupo_materia_docente', $idGrupoMateriaDocente)
            ->orderBy('id')
            ->get();
    }

    /**
     * Crea un nuevo elemento de competencia (siempre en estado "activo").
     */
    public function crear(array $data, int $userId): ElementoCompetencia
    {
        $gmd = GrupoMateriaDocente::findOrFail($data['id_grupo_materia_docente']);
        $this->verificarAccesoDocenteOAdmin($gmd, $userId);

        return ElementoCompetencia::create([
            'id_grupo_materia_docente' => $data['id_grupo_materia_docente'],
            'nombre' => $data['nombre'],
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => 'activo',
        ]);
    }

    /**
     * Actualiza un elemento de competencia respetando las reglas de estado.
     */
    public function actualizar(array $data, int $userId): ElementoCompetencia
    {
        $ec = ElementoCompetencia::with('grupoMateriaDocente')->findOrFail($data['id']);
        $this->verificarAccesoDocenteOAdmin($ec->grupoMateriaDocente, $userId);

        // Si está inactivo, solo se permite reactivar
        if ($ec->estado === 'inactivo') {
            if (isset($data['estado']) && $data['estado'] === 'activo') {
                $ec->update(['estado' => 'activo']);
                return $ec->fresh();
            }
            // Cualquier otro intento de modificación es rechazado
            throw new HttpException(422, 'Solo se puede reactivar un elemento inactivo.');
        }

        // Si está activo, se permite modificar cualquier campo permitido
        $ec->update($data);

        return $ec->fresh();
    }

    /**
     * Verifica que el usuario autenticado sea el docente del GMD o un administrador.
     */
    private function verificarAccesoDocenteOAdmin(GrupoMateriaDocente $gmd, int $userId): void
    {
        $user = request()->user();

        // Si es administrador, tiene acceso total
        if ($user->roles()->pluck('rol')->contains('Administrador')) {
            return;
        }

        // Si es docente, debe ser el asignado al GMD
        if ($gmd->idDocente !== $userId) {
            throw new HttpException(403, 'No tienes permiso para gestionar este grupo.');
        }
    }
}