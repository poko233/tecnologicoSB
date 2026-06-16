<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

class EmpresaService
{
    private const IMAGENES = [
        'LOGO_CUADRADO' => 'logo_cuadrado',
        'LOGO_LARGO'    => 'logo_largo',
        'BANER_INICIO'  => 'baner_inicio',
        'ICONO'         => 'icono',
    ];

    public function obtener(): Empresa
    {
        $empresa = Empresa::first();

        if (!$empresa) {
            throw new ModelNotFoundException('No se encontró configuración de empresa.');
        }

        return $this->conUrls($empresa);
    }

    public function actualizar(array $datos, array $archivos = []): Empresa
    {
        $empresa = Empresa::first();

        if (!$empresa) {
            throw new ModelNotFoundException('No se encontró configuración de empresa.');
        }

        foreach (self::IMAGENES as $campo => $nombreBase) {
            if (isset($archivos[$campo]) && $archivos[$campo] instanceof UploadedFile) {
                $datos[$campo] = $this->guardarImagen($archivos[$campo], $nombreBase);
            } else {
                unset($datos[$campo]);
            }
        }

        $empresa->fill($datos);
        $empresa->save();

        return $this->conUrls($empresa->fresh());
    }

    /**
     * Convierte las rutas relativas guardadas en BD a URLs absolutas accesibles.
     */
    private function conUrls(Empresa $empresa): Empresa
    {
        foreach (array_keys(self::IMAGENES) as $campo) {
            $valor = $empresa->{$campo};

            if ($valor) {
                $empresa->{$campo} = url($valor);
            }
        }

        return $empresa;
    }

    private function guardarImagen(UploadedFile $archivo, string $nombreBase): string
    {
        $directorio = public_path('empresa');

        if (!File::exists($directorio)) {
            File::makeDirectory($directorio, 0755, true);
        }

        foreach (File::glob("{$directorio}/{$nombreBase}.*") as $previo) {
            File::delete($previo);
        }

        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreArchivo = "{$nombreBase}.{$extension}";

        $archivo->move($directorio, $nombreArchivo);

        return "/empresa/{$nombreArchivo}";
    }
}