<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Modificar tabla Carrera
        Schema::table('Carrera', function (Blueprint $table) {
            // Renombrar 'costo' a 'costo_matricula' para evitar confusiones
            $table->renameColumn('costo', 'costo_matricula');

            // Nuevos campos
            $table->enum('regimen', ['Anual', 'Semestral', 'Mensual', 'Otro'])
                ->default('Mensual')
                ->after('codigo');
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
            // Verificar si existe la clave foránea antes de eliminarla
            $foreignKeys = $this->listTableForeignKeys('Cuota');
            if (in_array('cuota_idplanpago_foreign', $foreignKeys)) {
                $table->dropForeign('cuota_idplanpago_foreign');
            }
            // Eliminar la columna idPlanPago (si existe)
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

        // 4. Eliminar la tabla PlanPago (después de haber roto la dependencia)
        Schema::dropIfExists('PlanPago');
    }

    public function down(): void
    {
        // Revertir: restaurar PlanPago, recuperar columna idPlanPago, quitar idCarrera
        // y restaurar columnas de Carrera a su estado original.

        // 1. Recrear tabla PlanPago (estructura original)
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

        // 2. Restaurar columna idPlanPago en Cuota (nullable)
        Schema::table('Cuota', function (Blueprint $table) {
            $table->dropForeign(['idCarrera']);
            $table->dropColumn('idCarrera');
            $table->unsignedBigInteger('idPlanPago')->nullable()->after('idCuota');
            $table->foreign('idPlanPago')
                ->references('id')
                ->on('PlanPago')
                ->onDelete('cascade');
        });

        // 3. Revertir cambios en Carrera
        Schema::table('Carrera', function (Blueprint $table) {
            $table->renameColumn('costo_matricula', 'costo');
            $table->dropColumn(['regimen', 'duracion_meses', 'cuota_mensual', 'cuotas_por_anio']);
        });
    }

    /**
     * Helper para obtener los nombres de las claves foráneas de una tabla (simple, funciona en MySQL).
     * Si usas otro motor, ajusta según tu base de datos.
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