<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabla ElementoCompetencia
        Schema::create('ElementoCompetencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_grupo_materia_docente');
            $table->string('nombre', 150);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('id_grupo_materia_docente')
                ->references('idGrupoMateriaDocente')
                ->on('GrupoMateriaDocente')
                ->cascadeOnDelete();
        });

        // Tabla NotaElementoCompetencia
        Schema::create('NotaElementoCompetencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_elemento_competencia');
            $table->unsignedBigInteger('id_inscripcion');
            $table->decimal('puntaje', 5, 2); // 0.00 - 100.00
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['id_elemento_competencia', 'id_inscripcion'], 'nota_ec_unique');

            $table->foreign('id_elemento_competencia')
                ->references('id')
                ->on('ElementoCompetencia')
                ->cascadeOnDelete();

            $table->foreign('id_inscripcion')
                ->references('idInscripcion')
                ->on('Inscripcion')
                ->restrictOnDelete();
        });

        // Tabla NotaFinal
        Schema::create('NotaFinal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_inscripcion');
            $table->unsignedBigInteger('id_grupo_materia_docente');
            $table->decimal('nota_asistencia', 5, 2);    // 0.00 - 10.00
            $table->decimal('nota_academica', 5, 2);     // 0.00 - 90.00
            $table->decimal('nota_final', 5, 2);         // 0.00 - 100.00
            $table->enum('estado', ['Aprobado', 'Reprobado', 'Abandono']);
            $table->decimal('segunda_instancia_nota', 5, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('calificado_por')->nullable();
            $table->timestamps();

            $table->unique(['id_inscripcion', 'id_grupo_materia_docente'], 'nota_final_unique');

            $table->foreign('id_inscripcion')
                ->references('idInscripcion')
                ->on('Inscripcion')
                ->restrictOnDelete();

            $table->foreign('id_grupo_materia_docente')
                ->references('idGrupoMateriaDocente')
                ->on('GrupoMateriaDocente')
                ->restrictOnDelete();

            $table->foreign('calificado_por')
                ->references('id')
                ->on('user')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NotaFinal');
        Schema::dropIfExists('NotaElementoCompetencia');
        Schema::dropIfExists('ElementoCompetencia');
    }
};