<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mejoras para sistema de permisos dinámicos y sidebar configurable.
     * - modulo: agrega 'sidebar' (visible en menú) y 'orden'.
     * - formulario: agrega 'icono', 'componente', 'orden'.
     * - permiso: tabla pivote rol↔formulario con flags CRUD.
     *   Es la fuente de verdad del sidebar dinámico: un módulo aparece
     *   en el sidebar solo si el rol del usuario tiene 'ver=true'
     *   en al menos un formulario de ese módulo.
     */
    public function up(): void
    {
        // --- Nuevas columnas en 'modulo' ---
        Schema::table('modulo', function (Blueprint $table) {
            $table->boolean('sidebar')->default(true)->after('icono')
                  ->comment('Indica si el módulo aparece en el sidebar');
            $table->unsignedTinyInteger('orden')->default(0)->after('sidebar')
                  ->comment('Orden de aparición en el sidebar');
        });

        // --- Nuevas columnas en 'formulario' ---
        Schema::table('formulario', function (Blueprint $table) {
            $table->string('componente', 80)->nullable()->after('ruta')
                  ->comment('Nombre del componente frontend (Vue/React)');
            $table->string('icono', 60)->nullable()->after('componente')
                  ->comment('Icono del formulario en el sidebar');
            $table->unsignedTinyInteger('orden')->default(0)->after('icono')
                  ->comment('Orden dentro del módulo');
        });

        // --- Tabla permiso: rol ↔ formulario con acciones CRUD ---
        Schema::create('permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->foreignId('id_formulario')->constrained('formulario')->onDelete('cascade');
            $table->boolean('ver')->default(false);
            $table->boolean('crear')->default(false);
            $table->boolean('editar')->default(false);
            $table->boolean('eliminar')->default(false);
            $table->timestamps();
            $table->unique(['id_rol', 'id_formulario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permiso');

        Schema::table('formulario', function (Blueprint $table) {
            $table->dropColumn(['componente', 'icono', 'orden']);
        });

        Schema::table('modulo', function (Blueprint $table) {
            $table->dropColumn(['sidebar', 'orden']);
        });
    }
};
