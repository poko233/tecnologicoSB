<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ListaAsistencia', function (Blueprint $table) {
            $table->unsignedBigInteger('id_grupo_materia_docente')
                  ->nullable()
                  ->after('fecha_fin');

            $table->foreign('id_grupo_materia_docente', 'la_grupo_materia_docente_foreign')
                  ->references('idGrupoMateriaDocente')
                  ->on('GrupoMateriaDocente')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('ListaAsistencia', function (Blueprint $table) {
            $table->dropForeign('la_grupo_materia_docente_foreign');
            $table->dropColumn('id_grupo_materia_docente');
        });
    }
};
