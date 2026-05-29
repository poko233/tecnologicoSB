<?php

namespace App\Observers;

use App\Models\User;
use App\Services\QrService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        try {
            $qrService = new QrService();
            $qrCode = $qrService->generateQrImage($user->id);
            
            // Guardar la imagen QR generada en base64 sin disparar otros eventos
            $user->updateQuietly([
                'codigo_qr' => $qrCode
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar código QR para el usuario ID ' . $user->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Si por alguna razón el usuario no tiene código QR, se le genera uno
        if (empty($user->codigo_qr)) {
            try {
                $qrService = new QrService();
                $qrCode = $qrService->generateQrImage($user->id);
                
                $user->updateQuietly([
                    'codigo_qr' => $qrCode
                ]);
            } catch (\Exception $e) {
                Log::error('Error al generar código QR para el usuario ID ' . $user->id . ' en actualización: ' . $e->getMessage());
            }
        }
    }
}
