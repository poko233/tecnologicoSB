<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Crear tabla Gestion
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

        // 2. Crear tabla Estudiante
        Schema::create('Estudiante', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario')->primary();
            $table->string('matricula', 30)->unique();
            $table->timestamps();

            $table->foreign('id_usuario')
                ->references('id')
                ->on('user')
                ->cascadeOnDelete();
        });

        // 3. Migrar datos de user.matricula a Estudiante (solo para estudiantes)
        //    Se asume que los estudiantes tienen rol 2 (Estudiante) en la tabla user_rol
        DB::statement('
            INSERT INTO Estudiante (id_usuario, matricula, created_at, updated_at)
            SELECT u.id, u.matricula, NOW(), NOW()
            FROM user u
            INNER JOIN user_rol ur ON u.id = ur.id_user
            WHERE ur.id_rol = 2 AND u.matricula IS NOT NULL AND u.matricula != ""
        ');

        // 4. Eliminar la columna matricula de user
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('matricula');
        });
    }

    public function down(): void
    {
        // Restaurar la columna matricula en user
        Schema::table('user', function (Blueprint $table) {
            $table->string('matricula', 15)->nullable()->after('direccion');
        });

        // Devolver los datos de matricula desde Estudiante a user (opcional, solo si existían)
        DB::statement('
            UPDATE user u
            INNER JOIN Estudiante e ON u.id = e.id_usuario
            SET u.matricula = e.matricula
            WHERE e.matricula IS NOT NULL
        ');

        // Eliminar tablas
        Schema::dropIfExists('Estudiante');
        Schema::dropIfExists('Gestion');
    }
};