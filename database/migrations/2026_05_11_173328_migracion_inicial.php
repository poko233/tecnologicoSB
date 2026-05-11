<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar las migraciones.
     * El orden es crítico: primero entidades fuertes, luego tablas pivote.
     */
    public function up(): void
    {
        // --- ENTIDADES INDEPENDIENTES ---

        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('usuario', 40)->unique();
            $table->string('password', 80);
            $table->string('ci', 12)->unique();
            $table->string('nombres', 40);
            $table->string('apellidos', 40);
            $table->enum('genero', ['MASCULINO', 'FEMENINO']);
            $table->date('fecha_nac');
            $table->string('email', 80)->nullable();
            $table->string('telefono', 10)->nullable();
            $table->string('celular', 10)->nullable();
            $table->text('codigo_qr')->nullable();
            $table->string('verificacion', 40)->nullable();
            $table->string('foto', 80)->nullable();
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('sucursal', function (Blueprint $table) {
            $table->id();
            $table->string('sucursal', 40);
            $table->string('empresa', 40);
            $table->string('responsable', 40);
            $table->string('direccion', 80);
            $table->string('longitud', 40)->nullable();
            $table->string('latitud', 40)->nullable();
            $table->string('telefono', 10)->nullable();
            $table->string('celular', 10)->nullable();
            $table->string('email', 40)->nullable();
            $table->string('pais', 20);
            $table->string('ciudad', 20);
            $table->string('localidad', 30)->nullable();
            $table->string('imagen')->nullable();
            $table->string('qr')->nullable();
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->timestamps();
        });

        Schema::create('rol', function (Blueprint $table) {
            $table->id();
            $table->string('rol', 40)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('modulo', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 40);
            $table->text('descripcion')->nullable();
            $table->text('icono')->nullable();
            $table->timestamps();
        });

        Schema::create('formulario', function (Blueprint $table) {
            $table->id();
            $table->string('formulario', 40);
            $table->text('descripcion')->nullable();
            $table->string('ruta', 40)->nullable();
            $table->timestamps();
        });

        // --- TABLAS PIVOTE (RELACIONES) ---

        Schema::create('user_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('user')->onDelete('cascade');
            $table->foreignId('id_sucursal')->constrained('sucursal')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_user', 'id_sucursal']);
        });

        Schema::create('user_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('user')->onDelete('cascade');
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_user', 'id_rol']);
        });

        Schema::create('modulo_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->foreignId('id_modulo')->constrained('modulo')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_rol', 'id_modulo']);
        });

        Schema::create('formulario_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_modulo')->constrained('modulo')->onDelete('cascade');
            $table->foreignId('id_formulario')->constrained('formulario')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['id_modulo', 'id_formulario']);
        });
    }

    /**
     * Revertir las migraciones.
     * El orden de eliminación es inverso al de creación.
     */
    public function down(): void
    {
        // Primero eliminamos las tablas que tienen llaves foráneas
        Schema::dropIfExists('formulario_modulo');
        Schema::dropIfExists('modulo_rol');
        Schema::dropIfExists('user_rol');
        Schema::dropIfExists('user_sucursal');

        // Luego eliminamos las entidades base
        Schema::dropIfExists('formulario');
        Schema::dropIfExists('modulo');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('sucursal');
        Schema::dropIfExists('user');
    }
};