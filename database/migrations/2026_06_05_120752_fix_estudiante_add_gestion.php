<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('Gestion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->year('anio');
            $table->enum('periodo', ['Anual', 'Semestral', 'Trimestral', 'Mensual', 'Otro'])
                ->default('Anual');
            $table->enum('estado', ['activo', 'inactivo', 'cerrado'])
                ->default('activo');
            $table->timestamps();
        });

        Schema::create('Estudiante', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->primary();
            $table->string('matricula', 30)->unique();
            $table->timestamps();

            $table->foreign('id_usuario')
                ->references('id')
                ->on('user')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Estudiante');
        Schema::dropIfExists('Gestion');
    }
};