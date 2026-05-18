<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------------------------------------------------
        // 1. Crear tabla PlanPago
        // ---------------------------------------------------
        Schema::create('PlanPago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idUsuario')
                ->constrained('user')
                ->onDelete('cascade');
            $table->smallInteger('gestion'); // ej: 2026
            $table->decimal('matricula_economica', 10, 2)->default(0);
            $table->tinyInteger('numero_cuotas')->default(11); // 1, 10, 11
            $table->decimal('monto_cuota_promocion', 10, 2)->default(0);
            $table->decimal('monto_cuota_normal', 10, 2)->default(0);
            $table->string('matricula_numero', 15)->nullable();
            $table->enum('estado', ['pendiente_matricula', 'activo', 'inactivo'])
                ->default('pendiente_matricula');
            $table->timestamps();

            $table->index(['idUsuario', 'gestion']);
        });

        // ---------------------------------------------------
        // 2. Modificar tabla Cuota (existente)
        // ---------------------------------------------------
        Schema::table('Cuota', function (Blueprint $table) {
            // Relación con PlanPago (nullable para cuotas viejas sin plan)
            $table->unsignedBigInteger('idPlanPago')->nullable()->after('idCuota');
            $table->foreign('idPlanPago')
                ->references('id')
                ->on('PlanPago')
                ->onDelete('cascade');

            // Tipo de cuota: MATRICULA o MENSUAL
            $table->enum('tipo', ['MATRICULA', 'MENSUAL'])
                ->default('MENSUAL')
                ->after('numeroCuota');

            // Fecha de vencimiento
            $table->date('fecha_vencimiento')->nullable()->after('descuento');

            // Fecha de pago (si no existiera ya)
            if (!Schema::hasColumn('Cuota', 'fecha_pago')) {
                $table->dateTime('fecha_pago')->nullable()->after('estadoCuota');
            }
        });

        // ---------------------------------------------------
        // 3. Crear tabla Pago
        // ---------------------------------------------------
        Schema::create('Pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idCuota')->unique(); // un pago por cuota
            $table->foreignId('idUsuario')
                ->constrained('user')
                ->onDelete('cascade');
            $table->decimal('monto', 10, 2);
            $table->enum('metodo', ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA', 'QR'])
                ->default('EFECTIVO');
            $table->string('comprobante', 80)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')
                ->constrained('user')
                ->onDelete('cascade');
            $table->timestamps();

            $table->foreign('idCuota')
                ->references('idCuota')
                ->on('Cuota')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Pago');

        Schema::table('Cuota', function (Blueprint $table) {
            $table->dropForeign(['idPlanPago']);
            $table->dropColumn('idPlanPago');
            $table->dropColumn('tipo');
            $table->dropColumn('fecha_vencimiento');
            // No eliminamos fecha_pago porque puede ser parte del esquema original
        });

        Schema::dropIfExists('PlanPago');
    }
};