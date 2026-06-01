<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Agregar columna fecha (nullable temporalmente)
        Schema::table('ListaAsistenciaInscripcion', function (Blueprint $table) {
            $table->date('fecha')->nullable()->after('tipo');
        });

        // Rellenar registros existentes con la fecha de creación
        DB::table('ListaAsistenciaInscripcion')
            ->whereNull('fecha')
            ->update(['fecha' => DB::raw('DATE(created_at)')]);

        // Hacer NOT NULL
        Schema::table('ListaAsistenciaInscripcion', function (Blueprint $table) {
            $table->date('fecha')->nullable(false)->change();
        });

        // Agregar columna idHorario (nullable FK a Horario)
        Schema::table('ListaAsistenciaInscripcion', function (Blueprint $table) {
            $table->unsignedBigInteger('idHorario')->nullable()->after('fecha');
            $table->foreign('idHorario')
                ->references('idHorario')->on('Horario')
                ->onDelete('set null');
        });

        // Crear índice único compuesto con fecha y horario
        Schema::table('ListaAsistenciaInscripcion', function (Blueprint $table) {
            $table->unique(
                ['idInscripcion', 'idListaAsistencia', 'fecha', 'idHorario'],
                'unique_asistencia_diaria_horario'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ListaAsistenciaInscripcion', function (Blueprint $table) {
            $table->dropUnique('unique_asistencia_diaria_horario');
            $table->dropForeign(['idHorario']);
            $table->dropColumn('idHorario');
            $table->dropColumn('fecha');
        });
    }
};