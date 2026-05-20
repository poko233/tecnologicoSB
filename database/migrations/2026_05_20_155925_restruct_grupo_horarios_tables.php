<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | MODIFICAR TABLA GRUPO
        |--------------------------------------------------------------------------
        */

        Schema::table('Grupo', function (Blueprint $table) {

            // eliminar columnas viejas
            if (Schema::hasColumn('Grupo', 'hora_inicio')) {
                $table->dropColumn('hora_inicio');
            }

            if (Schema::hasColumn('Grupo', 'hora_fin')) {
                $table->dropColumn('hora_fin');
            }

            // agregar paralelo si no existe
            if (!Schema::hasColumn('Grupo', 'paralelo')) {
                $table->string('paralelo', 50)->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | TABLA HORARIO
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('Horario')) {

            Schema::create('Horario', function (Blueprint $table) {

                $table->id('idHorario');

                $table->time('horaInicio');
                $table->time('horaFin');

                $table->string('dia', 50);

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | TABLA GRUPO HORARIO
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('GrupoHorario')) {

            Schema::create('GrupoHorario', function (Blueprint $table) {

                $table->id('idGrupoHorario');

                $table->unsignedBigInteger('idGrupo');
                $table->unsignedBigInteger('idHorario');

                $table->timestamps();

                $table->foreign('idGrupo')
                    ->references('idGrupo')
                    ->on('Grupo')
                    ->onDelete('cascade');

                $table->foreign('idHorario')
                    ->references('idHorario')
                    ->on('Horario')
                    ->onDelete('cascade');

                $table->unique([
                    'idGrupo',
                    'idHorario'
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('GrupoHorario');

        Schema::dropIfExists('Horario');

        Schema::table('Grupo', function (Blueprint $table) {

            if (!Schema::hasColumn('Grupo', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable();
            }

            if (!Schema::hasColumn('Grupo', 'hora_fin')) {
                $table->time('hora_fin')->nullable();
            }
        });
    }
};