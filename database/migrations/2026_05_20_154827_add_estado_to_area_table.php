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
        Schema::table('Area', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->after('descripccion');
        });
    }

    public function down(): void
    {
        Schema::table('Area', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
