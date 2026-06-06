<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ejecuta la migración: añade la columna "estado" a la tabla "ElementoCompetencia".
     */
    public function up(): void
    {
        Schema::table('ElementoCompetencia', static function (Blueprint $table) {
            $table->enum('estado', ['activo', 'inactivo'])
                ->default('activo')
                ->after('observaciones');
        });
    }

    /**
     * Revierte la migración: elimina la columna "estado".
     */
    public function down(): void
    {
        Schema::table('ElementoCompetencia', static function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};