<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('qr:generate-bulk', function () {
    $this->info('Iniciando generación masiva de códigos QR para Estudiantes...');

    $students = \App\Models\User::whereHas('roles', function ($q) {
        $q->where('rol', 'Estudiante');
    })->get()->filter(function ($student) {
        return empty($student->codigo_qr) || !str_starts_with($student->codigo_qr, 'data:image/');
    });

    $count = $students->count();

    if ($count === 0) {
        $this->info('No hay estudiantes sin código QR o con formato antiguo. ¡Todo al día!');
        return;
    }

    $this->info("Se encontraron {$count} estudiantes sin código QR. Generando...");

    $bar = $this->output->createProgressBar($count);
    $bar->start();

    $qrService = new \App\Services\QrService();
    $exitos = 0;

    foreach ($students as $student) {
        try {
            $qrCode = $qrService->generateQrImage($student->id);
            $student->updateQuietly([
                'codigo_qr' => $qrCode
            ]);
            $exitos++;
        } catch (\Exception $e) {
            $this->error("\nError con el estudiante ID {$student->id}: " . $e->getMessage());
        }
        $bar->advance();
    }

    $bar->finish();
    $this->info("\n\nProceso completado. Se generaron {$exitos} de {$count} códigos QR con éxito.");
})->purpose('Genera códigos QR encriptados para todos los estudiantes que no lo tengan aún.');
