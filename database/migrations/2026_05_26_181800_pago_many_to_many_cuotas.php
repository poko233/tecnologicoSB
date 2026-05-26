<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Crear tabla pivote pago_cuota
        Schema::create('pago_cuota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idPago');
            $table->unsignedBigInteger('idCuota');
            $table->decimal('monto_pagado', 10, 2);
            $table->timestamps();

            $table->foreign('idPago')
                ->references('id')
                ->on('Pago')
                ->onDelete('cascade');

            $table->foreign('idCuota')
                ->references('idCuota')
                ->on('Cuota')
                ->onDelete('cascade');
        });

        // 2. Migrar datos existentes de Pago a pago_cuota
        $pagosExistentes = DB::table('Pago')->select('id', 'idCuota', 'monto')->get();
        foreach ($pagosExistentes as $pago) {
            DB::table('pago_cuota')->insert([
                'idPago' => $pago->id,
                'idCuota' => $pago->idCuota,
                'monto_pagado' => $pago->monto,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Eliminar relación idCuota en tabla Pago
        Schema::table('Pago', function (Blueprint $table) {
            $table->dropForeign('pago_idcuota_foreign');
            $table->dropUnique('pago_idcuota_unique');
            $table->dropColumn('idCuota');
        });
    }

    public function down(): void
    {
        // 1. Volver a agregar columna idCuota en Pago (nullable inicialmente)
        Schema::table('Pago', function (Blueprint $table) {
            $table->unsignedBigInteger('idCuota')->nullable()->after('id');
        });

        // 2. Restaurar relaciones desde pago_cuota a Pago
        $pivotRecords = DB::table('pago_cuota')->orderBy('id', 'asc')->get();
        foreach ($pivotRecords as $record) {
            DB::table('Pago')
                ->where('id', $record->idPago)
                ->whereNull('idCuota')
                ->update(['idCuota' => $record->idCuota]);
        }

        // 3. Eliminar tabla pivote
        Schema::dropIfExists('pago_cuota');

        // 4. Volver a aplicar restricciones unique y foreign en Pago
        Schema::table('Pago', function (Blueprint $table) {
            // Aseguramos que no quede en null si hay registros
            $table->unsignedBigInteger('idCuota')->nullable(false)->change();

            $table->unique('idCuota', 'pago_idcuota_unique');
            $table->foreign('idCuota', 'pago_idcuota_foreign')
                ->references('idCuota')
                ->on('Cuota')
                ->onDelete('cascade');
        });
    }
};
