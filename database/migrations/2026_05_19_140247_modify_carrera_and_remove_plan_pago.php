<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Modificar tabla Carrera (agregar campos)
        Schema::table('Carrera', function (Blueprint $table) {
            // Campo tipo (nuevo)
            $table->string('tipo', 20)->nullable()->after('codigo');

            // costo_matricula (nuevo)
            $table->decimal('costo_matricula', 10, 2)->default(0.00)->after('costo');

            // Nuevos campos para régimen y duración
            $table->enum('regimen', ['Anual', 'Semestral', 'Mensual', 'Otro'])
                ->default('Mensual')
                ->after('tipo');
            $table->unsignedInteger('duracion_meses')
                ->default(0)
                ->comment('Duración total en meses (ej: 36 para 3 años)')
                ->after('regimen');
            $table->decimal('cuota_mensual', 10, 2)
                ->default(0.00)
                ->after('duracion_meses');
            $table->tinyInteger('cuotas_por_anio')
                ->unsigned()
                ->default(12)
                ->comment('Número de cuotas por año (12=anual, 6=semestral, 1=mensual)')
                ->after('cuota_mensual');
        });

        // 2. Eliminar relación con PlanPago en Cuota
        Schema::table('Cuota', function (Blueprint $table) {
            $foreignKeys = $this->listTableForeignKeys('Cuota');
            if (in_array('cuota_idplanpago_foreign', $foreignKeys)) {
                $table->dropForeign('cuota_idplanpago_foreign');
            }
            if (Schema::hasColumn('Cuota', 'idPlanPago')) {
                $table->dropColumn('idPlanPago');
            }
        });

        // 3. Agregar idCarrera a Cuota
        Schema::table('Cuota', function (Blueprint $table) {
            $table->unsignedBigInteger('idCarrera')->nullable()->after('idUsuario');
            $table->foreign('idCarrera')
                ->references('idCarrera')
                ->on('Carrera')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });

        // 4. Eliminar la tabla PlanPago
        Schema::dropIfExists('PlanPago');
    }

    public function down(): void
    {
        // 1. Recrear PlanPago
        Schema::create('PlanPago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idUsuario')->constrained('user')->onDelete('cascade');
            $table->smallInteger('gestion');
            $table->decimal('matricula_economica', 10, 2)->default(0);
            $table->tinyInteger('numero_cuotas')->default(11);
            $table->decimal('monto_cuota_promocion', 10, 2)->default(0);
            $table->decimal('monto_cuota_normal', 10, 2)->default(0);
            $table->string('matricula_numero', 15)->nullable()->unique();
            $table->enum('estado', ['pendiente_matricula', 'activo', 'inactivo'])->default('pendiente_matricula');
            $table->timestamps();
            $table->index(['idUsuario', 'gestion']);
        });

        // 2. Restaurar idPlanPago en Cuota
        Schema::table('Cuota', function (Blueprint $table) {
            $table->dropForeign(['idCarrera']);
            $table->dropColumn('idCarrera');
            $table->unsignedBigInteger('idPlanPago')->nullable()->after('idCuota');
            $table->foreign('idPlanPago')->references('id')->on('PlanPago')->onDelete('cascade');
        });

        // 3. Revertir cambios en Carrera (eliminar los campos añadidos)
        Schema::table('Carrera', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'costo_matricula', 'regimen', 'duracion_meses', 'cuota_mensual', 'cuotas_por_anio']);
        });
    }

    /**
     * Helper para obtener los nombres de las claves foráneas de una tabla (solo MySQL).
     */
    private function listTableForeignKeys(string $table): array
    {
        $conn = Schema::getConnection();
        $databaseName = $conn->getDatabaseName();
        $result = $conn->select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$databaseName, $table]);
        return array_column($result, 'CONSTRAINT_NAME');
    }
};