<?php

namespace App\Observers;

use App\Models\CarreraUsuario;
use App\Services\GenerarCuotasService;
use Illuminate\Support\Facades\Log;

class CarreraUsuarioObserver
{
    /**
     * Handle the CarreraUsuario "created" event.
     */
    public function created(CarreraUsuario $carreraUsuario): void
    {
        try {
            GenerarCuotasService::generarCuotasPorCarrera(
                $carreraUsuario->idUsuario,
                $carreraUsuario->idCarrera,
                null // fecha de inicio por defecto (hoy en Bolivia)
            );
        } catch (\Exception $e) {
            // Registrar el error pero no detener la ejecución
            Log::error('Error al generar cuotas para CarreraUsuario ID ' . $carreraUsuario->idCarreraUsuario . ': ' . $e->getMessage());
            // Opcional: lanzar excepción si quieres que falle la inserción
            // throw $e;
        }
    }
}