<?php
 
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmpresaRequest;
use App\Services\EmpresaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;
 
class EmpresaController extends Controller
{
    public function __construct(
        private readonly EmpresaService $empresaService
    ) {}
 
    public function show(): JsonResponse
    {
        try {
            $empresa = $this->empresaService->obtener();
 
            return response()->json([
                'success' => true,
                'data'    => $empresa,
            ]);
 
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
 
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración de empresa.',
            ], 500);
        }
    }
 
    public function update(UpdateEmpresaRequest $request): JsonResponse
    {
        try {
            $archivos = [];
            foreach (['LOGO_CUADRADO', 'LOGO_LARGO', 'BANER_INICIO', 'ICONO'] as $campo) {
                if ($request->hasFile($campo)) {
                    $archivos[$campo] = $request->file($campo);
                }
            }

            $empresa = $this->empresaService->actualizar(
                $request->validated(),
                $archivos
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente.',
                'data'    => $empresa,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración.',
                'debug_error' => $e->getMessage(),
                'debug_file' => $e->getFile() . ':' . $e->getLine(),
                'debug_trace' => collect($e->getTrace())->take(5)->toArray(),
            ], 500);
        }
    }
}