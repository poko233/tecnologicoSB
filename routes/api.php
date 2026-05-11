<?php
use App\Http\Controllers\RolController;

Route::apiResource('roles', RolController::class);
Route::apiResource('modulos', ModuloController::class);
Route::prefix('modulos/{modulo}/formularios')->group(function () {
    Route::get('/',          [FormularioModuloController::class, 'index']);
    Route::post('/',         [FormularioModuloController::class, 'store']);
    Route::put('/{formulario}',    [FormularioModuloController::class, 'update']);
    Route::delete('/{formulario}', [FormularioModuloController::class, 'destroy']);
    Route::post('/sync',     [FormularioModuloController::class, 'sync']);
});