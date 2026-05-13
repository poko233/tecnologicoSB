<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormularioModuloController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SidebarController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Recuperar contraseña
Route::post('/password/forgot-email', [PasswordResetController::class, 'sendCode']);
Route::post('/password/verify-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado
    Route::get('/user', [AuthController::class, 'user']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Sidebar dinámico
    |--------------------------------------------------------------------------
    */

    Route::get('/sidebar', [SidebarController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */

    Route::apiResource('roles', RolController::class);

    /*
    |--------------------------------------------------------------------------
    | Permisos
    |--------------------------------------------------------------------------
    */

    Route::prefix('roles/{rol}/permisos')->group(function () {

        Route::get('/', [PermisoController::class, 'index']);

        Route::post('/', [PermisoController::class, 'store']);

        Route::put('/{permiso}', [PermisoController::class, 'update']);

        Route::delete('/{permiso}', [PermisoController::class, 'destroy']);

        Route::post('/sync', [PermisoController::class, 'sync']);
    });

    /*
    |--------------------------------------------------------------------------
    | Módulos
    |--------------------------------------------------------------------------
    */

    Route::apiResource('modulos', ModuloController::class);

    /*
    |--------------------------------------------------------------------------
    | Formularios por módulo
    |--------------------------------------------------------------------------
    */

    Route::prefix('modulos/{modulo}/formularios')->group(function () {

        Route::get('/', [FormularioModuloController::class, 'index']);

        Route::post('/', [FormularioModuloController::class, 'store']);

        Route::put('/{formulario}', [FormularioModuloController::class, 'update']);

        Route::delete('/{formulario}', [FormularioModuloController::class, 'destroy']);

        Route::post('/sync', [FormularioModuloController::class, 'sync']);
    });
});