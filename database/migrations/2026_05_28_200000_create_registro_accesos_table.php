<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_accesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->string('tipo_persona');          // 'Estudiante', 'Docente', 'Administrativo'
            $table->string('estado_mostrado')->nullable();  // 'PASE', 'LLAMAR A CONTABILIDAD', etc.
            $table->string('color_alerta')->nullable();     // 'verde', 'amarillo', 'naranja', 'rojo'
            $table->string('punto_control')->nullable();    // identificador del punto de acceso
            $table->timestamp('fecha_hora')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_accesos');
    }
};
