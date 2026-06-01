<?php
 
namespace App\Services;
 
use App\Models\Empresa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
 
class EmpresaService
{
    
    public function obtener(): Empresa
    {
        $empresa = Empresa::first();
 
        if (!$empresa) {
            throw new ModelNotFoundException('No se encontró configuración de empresa.');
        }
 
        return $empresa;
    }
 
    
    public function actualizar(array $datos): Empresa
    {
        $empresa = $this->obtener();
        $empresa->fill($datos);
        $empresa->save();
 
        return $empresa->fresh();
    }
}