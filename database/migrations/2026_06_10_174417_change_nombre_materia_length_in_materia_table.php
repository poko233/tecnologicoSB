<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Materia', function (Blueprint $table) {
            $table->string('nombreMateria', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('Materia', function (Blueprint $table) {
            $table->string('nombreMateria', 50)->change();
        });
    }
};
