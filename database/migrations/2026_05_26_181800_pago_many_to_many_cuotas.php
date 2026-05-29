<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pago_cuota')) {
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
        }

        if (Schema::hasColumn('Pago', 'idCuota')) {
            $pagosExistentes = DB::table('Pago')
                ->select('id', 'idCuota', 'monto')
                ->whereNotNull('idCuota')
                ->get();

            foreach ($pagosExistentes as $pago) {
                DB::table('pago_cuota')->insert([
                    'idPago' => $pago->id,
                    'idCuota' => $pago->idCuota,
                    'monto_pagado' => $pago->monto ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'Pago'
                  AND COLUMN_NAME = 'idCuota'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($foreignKeys as $fk) {
                Schema::table('Pago', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                });
            }

            $indexes = DB::select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'Pago'
                  AND COLUMN_NAME = 'idCuota'
                  AND NON_UNIQUE = 0
            ");

            foreach ($indexes as $index) {
                if ($index->INDEX_NAME !== 'PRIMARY') {
                    Schema::table('Pago', function (Blueprint $table) use ($index) {
                        $table->dropUnique($index->INDEX_NAME);
                    });
                }
            }

            Schema::table('Pago', function (Blueprint $table) {
                $table->dropColumn('idCuota');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('Pago', 'idCuota')) {
            Schema::table('Pago', function (Blueprint $table) {
                $table->unsignedBigInteger('idCuota')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('pago_cuota')) {
            $pivotRecords = DB::table('pago_cuota')->orderBy('id', 'asc')->get();

            foreach ($pivotRecords as $record) {
                DB::table('Pago')
                    ->where('id', $record->idPago)
                    ->whereNull('idCuota')
                    ->update(['idCuota' => $record->idCuota]);
            }

            Schema::dropIfExists('pago_cuota');
        }

        Schema::table('Pago', function (Blueprint $table) {
            $table->foreign('idCuota', 'pago_idcuota_foreign')
                ->references('idCuota')
                ->on('Cuota')
                ->onDelete('cascade');
        });
    }
};