<?php
use App\Http\Controllers\RolController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\FormularioModuloController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SidebarController;
use App\Http\Controllers\PermisoController;
use Illuminate\Support\Facades\Route;

// Rutas Públicas de Autenticación
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas Protegidas por Token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',    [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sidebar dinámico — alimenta el menú lateral del frontend
    // Devuelve módulos + formularios filtrados por permisos del usuario
    Route::get('/sidebar', [SidebarController::class, 'index']);

    Route::apiResource('roles', RolController::class);

    // Permisos CRUD por Rol+Formulario (tab "Permisos" en Configuraciones)
    Route::prefix('roles/{rol}/permisos')->group(function () {
        Route::get('/',             [PermisoController::class, 'index']);
        Route::post('/',            [PermisoController::class, 'store']);
        Route::put('/{permiso}',    [PermisoController::class, 'update']);
        Route::delete('/{permiso}', [PermisoController::class, 'destroy']);
        Route::post('/sync',        [PermisoController::class, 'sync']);
    });

    Route::apiResource('modulos', ModuloController::class);

    // Formularios anidados bajo un módulo (tab "Formularios" en Configuraciones)
    Route::prefix('modulos/{modulo}/formularios')->group(function () {
        Route::get('/',               [FormularioModuloController::class, 'index']);
        Route::post('/',              [FormularioModuloController::class, 'store']);
        Route::put('/{formulario}',   [FormularioModuloController::class, 'update']);
        Route::delete('/{formulario}',[FormularioModuloController::class, 'destroy']);
        Route::post('/sync',          [FormularioModuloController::class, 'sync']);
    });
});