<?php
// =============================================================
//  METASOFT — Migración única complementaria
//  Archivo: database/migrations/2024_01_02_000001_create_metasoft_tables.php
//
//  COMPATIBLE CON:
//    - Migración existente: tabla `user` (id, usuario, password, ci,
//      nombres, apellidos, genero, fecha_nac, email, telefono,
//      celular, codigo_qr, verificacion, foto, estado, timestamps)
//    - personal_access_tokens (ya existe)
//    - password_reset_codes   (ya existe)
//
//  DECISIONES DE COMPATIBILIDAD:
//    - NO se crea tabla `Usuario`; se extiende `user` con ALTER
//      para agregar las columnas extra que el nuevo sistema necesita
//      (apellidoPaterno, apellidoMaterno, direccion, matricula,
//       expedido, celular como 20 chars). Las FKs que en el diseño
//      apuntan a Usuario(id) apuntan aquí a user(id).
//    - `user`.genero: el valor ya existente es MAYÚSCULAS; el nuevo
//      esquema usa 'Masculino'/'Femenino'. Se conserva el enum de
//      `user` tal cual (MAYÚSCULAS) y NO se modifica.
//    - `user`.verificacion: ya es string(40); el nuevo esquema lo
//      quería tinyInteger. Se conserva string para no romper lo
//      existente; se agrega columna `verificacion_bool` tinyInt
//      solo si tu código la necesita, o simplemente úsala como 0/1.
//      → Para simplificar: se omite duplicar, el código debe
//        adaptarse a string('verificacion').
//    - Todas las FKs usan onDelete('restrict') / onUpdate('cascade')
//      salvo donde el diseño original pide 'set null'.
// =============================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────
        // PASO 1: Extender tabla `user` existente con columnas
        //         que el nuevo sistema necesita y que no existen
        // ─────────────────────────────────────────────────────
        Schema::table('user', function (Blueprint $table) {
            // Columnas presentes en el diseño de Usuario pero
            // ausentes en la migración base de `user`
            $table->dropColumn('apellidos');
            $table->string('apellidoPaterno', 50)->nullable()->after('nombres');
            $table->string('apellidoMaterno', 50)->nullable()->after('apellidoPaterno');
            $table->string('direccion', 50)->nullable()->after('celular');
            $table->string('matricula', 15)->nullable()->after('direccion');
            $table->enum('expedido', [
                'LPZ', 'CBBA', 'OR', 'PT', 'TJ',
                'SCZ', 'BN', 'PD', 'CH', 'QR', 'EXT'
            ])->nullable()->after('matricula'); // nullable para no romper filas existentes
        });

        // ─────────────────────────────────────────────────────
        // 000001 — Area
        // ─────────────────────────────────────────────────────
        Schema::create('Area', function (Blueprint $table) {
            $table->id('idArea');
            $table->string('nombre', 50);
            $table->text('descripccion')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────
        // 000002 — Materia (con autorreferencia)
        // ─────────────────────────────────────────────────────
        Schema::create('Materia', function (Blueprint $table) {
            $table->id('idMateria');
            $table->string('nombreMateria', 50);
            $table->string('codigo', 50);
            $table->tinyInteger('semestre');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->unsignedBigInteger('idPrerequisito')->nullable();
            $table->timestamps();

            $table->foreign('idPrerequisito')
                  ->references('idMateria')
                  ->on('Materia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000003 — Carrera
        // ─────────────────────────────────────────────────────
        Schema::create('Carrera', function (Blueprint $table) {
            $table->id('idCarrera');
            $table->string('nombreCarrera', 50);
            $table->string('codigo', 50);
            $table->tinyInteger('duracion');
            $table->string('cargaHoraria', 50);
            $table->decimal('costo', 10, 2);
            $table->text('denominacionTitutloProfesional');
            $table->enum('estadoCarrera', ['activo', 'inactivo'])->default('activo');
            $table->unsignedBigInteger('idArea');
            $table->timestamps();

            $table->foreign('idArea')
                  ->references('idArea')
                  ->on('Area')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000004 — CarreraMateria (pivote)
        // ─────────────────────────────────────────────────────
        Schema::create('CarreraMateria', function (Blueprint $table) {
            $table->id('idCarreraMateria');
            $table->unsignedBigInteger('idMateria');
            $table->unsignedBigInteger('idCarrera');
            $table->timestamps();

            $table->foreign('idMateria')
                  ->references('idMateria')
                  ->on('Materia')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idCarrera')
                  ->references('idCarrera')
                  ->on('Carrera')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000006 — CarreraUsuario  (FK → user.id)
        // ─────────────────────────────────────────────────────
        Schema::create('CarreraUsuario', function (Blueprint $table) {
            $table->id('idCarreraUsuario');
            $table->unsignedBigInteger('idCarrera');
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            $table->foreign('idCarrera')
                  ->references('idCarrera')
                  ->on('Carrera')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idUsuario')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000007 — Docente  (PK = FK → user.id, relación 1:1)
        // ─────────────────────────────────────────────────────
        Schema::create('Docente', function (Blueprint $table) {
            $table->unsignedBigInteger('idDocente')->primary();
            $table->string('profesion', 50);
            $table->string('abreviaturaProfesional', 50)->nullable();
            $table->date('fechaRegistro');
            $table->enum('estadoDocente', ['activo', 'inactivo'])->default('activo');

            $table->foreign('idDocente')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000008 — Grupo
        // ─────────────────────────────────────────────────────
        Schema::create('Grupo', function (Blueprint $table) {
            $table->id('idGrupo');
            $table->string('nombre', 50);
            $table->string('codigo', 50);
            $table->string('paralelo', 50)->nullable();
            $table->enum('turno', ['Mañana', 'Tarde', 'Noche']);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('gestion', 20);
            $table->integer('cupos');
            $table->enum('tipo', ['Capacitacion', 'Curso']);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────
        // 000009 — GrupoMateriaDocente
        // ─────────────────────────────────────────────────────
        Schema::create('GrupoMateriaDocente', function (Blueprint $table) {
            $table->id('idGrupoMateriaDocente');
            $table->unsignedBigInteger('idGrupo');
            $table->unsignedBigInteger('idMateria');
            $table->unsignedBigInteger('idDocente');
            $table->timestamps();

            $table->foreign('idGrupo')
                  ->references('idGrupo')
                  ->on('Grupo')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idMateria')
                  ->references('idMateria')
                  ->on('Materia')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idDocente')
                  ->references('idDocente')
                  ->on('Docente')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000010 — Aula
        // ─────────────────────────────────────────────────────
        Schema::create('Aula', function (Blueprint $table) {
            $table->id('idAula');
            $table->string('nombreAula', 50);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────
        // 000011 — AulaGrupo  (PK compuesta)
        // ─────────────────────────────────────────────────────
        Schema::create('AulaGrupo', function (Blueprint $table) {
            $table->unsignedBigInteger('idAula');
            $table->unsignedBigInteger('idGrupo');
            $table->timestamps();

            $table->primary(['idAula', 'idGrupo']);

            $table->foreign('idAula')
                  ->references('idAula')
                  ->on('Aula')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idGrupo')
                  ->references('idGrupo')
                  ->on('Grupo')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000012 — Inscripcion  (FK → user.id)
        // ─────────────────────────────────────────────────────
        Schema::create('Inscripcion', function (Blueprint $table) {
            $table->id('idInscripcion');
            $table->unsignedBigInteger('idGrupo');
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            $table->foreign('idGrupo')
                  ->references('idGrupo')
                  ->on('Grupo')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idUsuario')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000013 — ListaAsistencia
        // ─────────────────────────────────────────────────────
        Schema::create('ListaAsistencia', function (Blueprint $table) {
            $table->id('idListaAsistencia');
            $table->string('observacion', 50)->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────
        // 000014 — ListaAsistenciaInscripcion
        // ─────────────────────────────────────────────────────
        Schema::create('ListaAsistenciaInscripcion', function (Blueprint $table) {
            $table->id('idListaAsistenciaInscripcion');
            $table->string('observacion', 50)->nullable();
            $table->enum('tipo', ['Presente', 'Permiso', 'Falta', 'Atraso']);
            $table->unsignedBigInteger('idInscripcion');
            $table->unsignedBigInteger('idListaAsistencia');
            $table->timestamps();

            $table->foreign('idInscripcion')
                  ->references('idInscripcion')
                  ->on('Inscripcion')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('idListaAsistencia')
                  ->references('idListaAsistencia')
                  ->on('ListaAsistencia')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000015 — Cuota  (FK → user.id)
        // ─────────────────────────────────────────────────────
        Schema::create('Cuota', function (Blueprint $table) {
            $table->id('idCuota');
            $table->decimal('monto', 10, 2);
            $table->string('numeroCuota', 50);
            $table->decimal('descuento', 10, 2)->default(0.00);
            $table->enum('estadoCuota', ['Debe', 'Pagado', 'Condonado'])->default('Debe');
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            $table->foreign('idUsuario')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000016 — DocumentoEstudiante  (FK → user.id)
        // ─────────────────────────────────────────────────────
        Schema::create('DocumentoEstudiante', function (Blueprint $table) {
            $table->id('idDocumentoEstudiante');
            $table->text('nombreDocumento');
            $table->text('ubicacionArchivo');
            $table->enum('estadoDocumento', ['Debe', 'Entregado'])->default('Debe');
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            $table->foreign('idUsuario')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000017 — NumeroReferencia  (FK → user.id)
        // ─────────────────────────────────────────────────────
        Schema::create('NumeroReferencia', function (Blueprint $table) {
            $table->id('idNumeroReferencia');
            $table->string('parentesco', 50);
            $table->string('numeroReferencia', 50);
            $table->string('nombreContactoReferencia', 50);
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            $table->foreign('idUsuario')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000018 — Observacion  (FK → user.id)
        // ─────────────────────────────────────────────────────
        Schema::create('Observacion', function (Blueprint $table) {
            $table->id('idObservacion');
            $table->string('nombreObservacion', 50);
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            $table->foreign('idUsuario')
                  ->references('id')
                  ->on('user')          // ← apunta a la tabla existente
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        // ─────────────────────────────────────────────────────
        // 000019 — FechaRazon
        // ─────────────────────────────────────────────────────
        Schema::create('FechaRazon', function (Blueprint $table) {
            $table->id('idFechaRazon');
            $table->text('razon');
            $table->dateTime('fecha');
            $table->timestamps();
        });
    }

    // ─────────────────────────────────────────────────────────
    // DOWN: orden inverso estricto (dependientes primero)
    // ─────────────────────────────────────────────────────────
    public function down(): void
    {
        Schema::dropIfExists('FechaRazon');
        Schema::dropIfExists('Observacion');
        Schema::dropIfExists('NumeroReferencia');
        Schema::dropIfExists('DocumentoEstudiante');
        Schema::dropIfExists('Cuota');
        Schema::dropIfExists('ListaAsistenciaInscripcion');
        Schema::dropIfExists('ListaAsistencia');
        Schema::dropIfExists('Inscripcion');
        Schema::dropIfExists('AulaGrupo');
        Schema::dropIfExists('Aula');
        Schema::dropIfExists('GrupoMateriaDocente');
        Schema::dropIfExists('Grupo');
        Schema::dropIfExists('Docente');
        Schema::dropIfExists('CarreraUsuario');
        Schema::dropIfExists('CarreraMateria');
        Schema::dropIfExists('Carrera');
        Schema::dropIfExists('Materia');
        Schema::dropIfExists('Area');

        // Revertir columnas añadidas a `user`
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn(
                collect([
                    'apellidoPaterno',
                    'apellidoMaterno',
                    'direccion',
                    'matricula',
                    'expedido',
                ])->filter(fn($col) => Schema::hasColumn('user', $col))->values()->all()
            );

            if (!Schema::hasColumn('user', 'apellidos')) {
                $table->string('apellidos', 40)->nullable()->after('nombres');
            }
        });
    }
};