<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('qr:generate-bulk', function () {
    $this->info('Iniciando generación masiva de códigos QR para todos los usuarios...');

    // Obtener todos los usuarios que no tengan QR o tengan formato antiguo (sin data:image/)
    $usuariosSinQR = \App\Models\User::all()->filter(function ($user) {
        return empty($user->codigo_qr) || !str_starts_with($user->codigo_qr, 'data:image/');
    });

    $total = $usuariosSinQR->count();

    if ($total === 0) {
        $this->info('Todos los usuarios ya tienen código QR válido. ¡Nada que hacer!');
        return;
    }

    $this->info("Se encontraron {$total} usuarios sin código QR. Generando...");

    $bar = $this->output->createProgressBar($total);
    $bar->start();

    $qrService = new \App\Services\QrService();
    $exitos = 0;

    foreach ($usuariosSinQR as $user) {
        try {
            $qrCode = $qrService->generateQrImage($user->id);
            $user->updateQuietly([
                'codigo_qr' => $qrCode
            ]);
            $exitos++;
        } catch (\Exception $e) {
            $this->error("\nError con el usuario ID {$user->id}: " . $e->getMessage());
        }
        $bar->advance();
    }

    $bar->finish();
    $this->info("\n\nProceso completado. Se generaron {$exitos} de {$total} códigos QR con éxito.");
})->purpose('Genera códigos QR encriptados para todos los usuarios que aún no lo tengan.');