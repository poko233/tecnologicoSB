<?php
use App\Http\Controllers\RolController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\FormularioModuloController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas de Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas Protegidas por Token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('roles', RolController::class);
    Route::apiResource('modulos', ModuloController::class);
    Route::prefix('modulos/{modulo}/formularios')->group(function () {
        Route::get('/',          [FormularioModuloController::class, 'index']);
        Route::post('/',         [FormularioModuloController::class, 'store']);
        Route::put('/{formulario}',    [FormularioModuloController::class, 'update']);
        Route::delete('/{formulario}', [FormularioModuloController::class, 'destroy']);
        Route::post('/sync',     [FormularioModuloController::class, 'sync']);
    });
});