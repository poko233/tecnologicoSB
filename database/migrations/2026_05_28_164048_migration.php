<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa', function (Blueprint $table) {
            $table->integer('ID_EMPRESA')->autoIncrement();
            $table->string('EMPRESA', 80);
            $table->text('SLOGAN');
            $table->string('SIGLA', 200);
            $table->string('TELEFONO', 11);
            $table->string('CELULAR', 11);
            $table->string('EMAIL', 80);
            $table->text('DIRECCION');
            $table->string('RESPONSABLE', 80);
            $table->string('LATITUD', 80);
            $table->string('LONGITUD', 80);
            $table->text('OBJETO');
            $table->text('MISION');
            $table->text('VISION');
            $table->string('FACEBOOK', 40);
            $table->string('INSTAGRAM', 40);
            $table->string('TIKTOK', 40);
            $table->string('LINKEDIN', 40);
            $table->enum('CARRITO', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->decimal('TIPO_CAMBIO', 10, 2);
            $table->string('LOGO_CUADRADO', 80);
            $table->string('LOGO_LARGO', 80);
            $table->string('BANER_INICIO', 80);
            $table->string('ICONO', 40);
            $table->string('TITULO_CIERRE', 80);
            $table->text('MENSAJE_CIERRE');
            $table->string('TITULO_INICIO', 80);
            $table->text('MENSAJE_INICIO');
            $table->string('DOMINIO', 200);
            $table->string('SMTP_CORREO', 100);
            $table->string('CORREO_INSTITUCIONAL', 80);
            $table->string('PWD_INSTITUCIONAL', 80);
        });

        Schema::create('formulario_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_rol')->constrained('rol')->onDelete('cascade');
            $table->foreignId('id_modulo')->constrained('modulo')->onDelete('cascade');
            $table->foreignId('id_formulario')->constrained('formulario')->onDelete('cascade');
            $table->tinyInteger('puede_crear')->default(0);
            $table->tinyInteger('puede_leer')->default(0);
            $table->tinyInteger('puede_editar')->default(0);
            $table->tinyInteger('puede_eliminar')->default(0);
            $table->timestamps();
            $table->unique(['id_rol', 'id_modulo', 'id_formulario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulario_permiso');
        Schema::dropIfExists('empresa');
    }
};