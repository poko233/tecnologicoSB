<?php

use App\Http\Controllers\ElementoCompetenciaController;
use App\Http\Controllers\NotasController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormularioController;
use App\Http\Controllers\FormularioModuloController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ModuloRolController;
use App\Http\Controllers\MisModulosController;
use App\Http\Controllers\AsignacionesController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\RecursosHumanosController;

use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\InscripcionAcademicaController;
use App\Http\Controllers\DocumentoEstudianteController;
use App\Http\Controllers\ResumenInscripcionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\AsignacionDocenteController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\DocenteAsistenciaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\PlanillaReporteController;
use App\Http\Controllers\ReporteCalificacionesController;

Route::get('/pagos/{id}/recibo', [ReciboController::class, 'descargar']);

Route::post('/qr/debug-generate', [QrController::class, 'debugGenerate']);
Route::post('/qr/regenerate-all', [QrController::class, 'regenerateAll']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/password/forgot-email', [PasswordResetController::class, 'sendCode']);
Route::post('/password/verify-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);

Route::post('/qr/verify-access', [QrController::class, 'verifyAccess']);
Route::post('/qr/verify-access-ci', [QrController::class, 'verifyAccessByCi']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('mis-modulos', MisModulosController::class);

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | CRUD Docentes
    |--------------------------------------------------------------------------
    */
    Route::get('/docentes', [DocenteController::class, 'index']);
    Route::post('/docentes', [DocenteController::class, 'store']);
    Route::put('/docentes/{idDocente}', [DocenteController::class, 'update']);
    Route::delete('/docentes/{idDocente}', [DocenteController::class, 'destroy']);
    Route::put('/docentes/{idDocente}/activar', [DocenteController::class, 'activar']);


    Route::get('/docente/grupos-asignados/{idGrupoMateriaDocente}/reporte/csv', [DocenteAsistenciaController::class, 'reporteCsv']);
    Route::get('/docente/grupos-asignados/{idGrupoMateriaDocente}/reporte/pdf', [DocenteAsistenciaController::class, 'reportePdf']);
    /*
    |--------------------------------------------------------------------------
    | Asistencia Docentes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:Docente,Administrador')->group(function () {
        Route::get('/docente/grupos-asignados', [DocenteAsistenciaController::class, 'gruposAsignados']);
        Route::get('/docente/grupos-asignados/{idGrupoMateriaDocente}/estudiantes', [DocenteAsistenciaController::class, 'estudiantes']);
        Route::post('/docente/asistencia', [DocenteAsistenciaController::class, 'registrarAsistencia']);
        Route::post('/docente/asistencia/batch', [DocenteAsistenciaController::class, 'batch']);

        Route::get('/notas/mis-grupos', [NotasController::class, 'misGrupos']);

        Route::post('/elementos-competencia/listar', [ElementoCompetenciaController::class, 'listar']);
        Route::post('/elementos-competencia/crear', [ElementoCompetenciaController::class, 'crear']);
        Route::post('/elementos-competencia/actualizar', [ElementoCompetenciaController::class, 'actualizar']);

        Route::post('/planilla', [NotasController::class, 'planilla']);
        Route::post('/planilla/guardar', [NotasController::class, 'guardarNotas']);
    });
    Route::get('/reportes/calificaciones/preview', [ReporteCalificacionesController::class, 'preview']);
    Route::get('/reportes/calificaciones/xlsx',    [ReporteCalificacionesController::class, 'xlsx']);
    Route::get('/reportes/calificaciones/pdf',     [ReporteCalificacionesController::class, 'pdf']);

    
    Route::get('/reportes/{idGrupoMateriaDocente}/excel',   [PlanillaReporteController::class, 'excel']);
    Route::get('/reportes/{idGrupoMateriaDocente}/pdf',     [PlanillaReporteController::class, 'pdf']);
    Route::get('/reportes/{idGrupoMateriaDocente}/pdf/ver', [PlanillaReporteController::class, 'pdfVer']);


   
    /*
    |--------------------------------------------------------------------------
    | Inscripción estudiante
    |--------------------------------------------------------------------------
    */

    Route::post('/estudiantes/verificar-datos', [
        EstudianteController::class,
        'verificarDatos'
    ]);

    Route::get('/estudiantes/continuar-inscripcion', [
        EstudianteController::class,
        'continuarInscripcion'
    ]);

    Route::get('/estudiantes/{id}/documentos-inscripcion', [
        EstudianteController::class,
        'documentosInscripcion'
    ]);

    Route::apiResource('estudiantes', EstudianteController::class);

    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);

    Route::get('/carreras', [CarreraController::class, 'index']);
    Route::get('/carreras/{carrera}', [CarreraController::class, 'show']);
    Route::get('/carreras/{idCarrera}/materias', [CarreraController::class, 'materias']);

    Route::get('/materias', [MateriaController::class, 'index']);
    Route::get('/materias/{materia}', [MateriaController::class, 'show']);
    Route::get('/materias/{idMateria}/grupos', [MateriaController::class, 'gruposPorMateria']);

    Route::get('/grupos', [GrupoController::class, 'index']);
    Route::get('/grupos/{grupo}', [GrupoController::class, 'show']);

    Route::get('/horarios', [HorarioController::class, 'index']);

    Route::post('/documentos-estudiante', [
        DocumentoEstudianteController::class,
        'store'
    ]);

    Route::get('/documentos-estudiante/{idUsuario}', [
        DocumentoEstudianteController::class,
        'documentosUsuario'
    ]);

    Route::post('/inscripciones-academicas', [
        InscripcionAcademicaController::class,
        'inscribir'
    ]);

    Route::get('/inscripcion/resumen/{idUsuario}', [
        ResumenInscripcionController::class,
        'show'
    ]);

    Route::post('/inscripcion/finalizar/{idUsuario}', [
        ResumenInscripcionController::class,
        'finalizar'
    ]);

    Route::post('/inscripcion/pago-cuotas', [
        InscripcionAcademicaController::class,
        'guardarPagoCuotas'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Asignaciones estudiantes
    |--------------------------------------------------------------------------
    */
    Route::prefix('asignaciones')->group(function () {
        Route::get('/estudiantes', [
            AsignacionesController::class,
            'estudiantes'
        ]);

        Route::get('/estudiantes/{idUsuario}', [
            AsignacionesController::class,
            'detalleEstudiante'
        ]);

        Route::get('/estudiantes/{idUsuario}/semestre-uno', [
            AsignacionesController::class,
            'materiasSemestreUno'
        ]);

        Route::post('/estudiantes/{idUsuario}/inscribir-semestre-uno', [
            AsignacionesController::class,
            'inscribirSemestreUno'
        ]);

        Route::put('/estudiantes/{idUsuario}', [
            AsignacionesController::class,
            'actualizarEstudiante'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Asignación de docentes a materias y grupos
    |--------------------------------------------------------------------------
    */
    Route::get('/asignacion-docente', [
        AsignacionDocenteController::class,
        'index'
    ]);

    Route::post('/asignacion-docente', [
        AsignacionDocenteController::class,
        'guardar'
    ]);

    Route::delete('/asignacion-docente/materia/{idMateria}', [
        AsignacionDocenteController::class,
        'eliminarPorMateria'
    ]);

    Route::delete('/asignacion-docente/{idMateria}/{idDocente}', [
        AsignacionDocenteController::class,
        'eliminarAsignacion'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Administración
    |--------------------------------------------------------------------------
    */
    Route::middleware('rol:1,2')->group(function () {
        Route::apiResource('areas', AreaController::class)
            ->except(['index', 'show']);

        Route::apiResource('materias', MateriaController::class)
            ->except(['index', 'show']);

        Route::post('/carreras', [
            CarreraController::class,
            'store'
        ]);

        Route::put('/carreras/{carrera}', [
            CarreraController::class,
            'update'
        ]);

        Route::delete('/carreras/{carrera}', [
            CarreraController::class,
            'destroy'
        ]);

        Route::post('/grupos', [
            GrupoController::class,
            'store'
        ]);

        Route::put('/grupos/{grupo}', [
            GrupoController::class,
            'update'
        ]);

        Route::delete('/grupos/{grupo}', [
            GrupoController::class,
            'destroy'
        ]);

        Route::post('/horarios', [
            HorarioController::class,
            'store'
        ]);

        Route::delete('/horarios/{horario}', [
            HorarioController::class,
            'destroy'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Roles y permisos
    |--------------------------------------------------------------------------
    */
    Route::apiResource('roles', RolController::class);

    Route::prefix('roles/{rol}/permisos')->group(function () {
        Route::get('/', [
            PermisoController::class,
            'index'
        ]);

        Route::post('/', [
            PermisoController::class,
            'store'
        ]);

        Route::put('/{permiso}', [
            PermisoController::class,
            'update'
        ]);

        Route::delete('/{permiso}', [
            PermisoController::class,
            'destroy'
        ]);

        Route::post('/sync', [
            PermisoController::class,
            'sync'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Módulos
    |--------------------------------------------------------------------------
    */
    Route::get('modulos', [
        ModuloController::class,
        'index'
    ]);

    Route::post('modulos', [
        ModuloController::class,
        'store'
    ]);

    Route::get('modulos/{id}', [
        ModuloController::class,
        'show'
    ]);

    Route::put('modulos/{id}', [
        ModuloController::class,
        'update'
    ]);

    Route::delete('modulos/{id}', [
        ModuloController::class,
        'destroy'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Formularios
    |--------------------------------------------------------------------------
    */
    Route::get('formularios', [
        FormularioController::class,
        'index'
    ]);

    Route::post('formularios', [
        FormularioController::class,
        'store'
    ]);

    Route::get('formularios/{id}', [
        FormularioController::class,
        'show'
    ]);

    Route::put('formularios/{id}', [
        FormularioController::class,
        'update'
    ]);

    Route::delete('formularios/{id}', [
        FormularioController::class,
        'destroy'
    ]);

    Route::get('formulario-modulo', [
        FormularioModuloController::class,
        'index'
    ]);

    Route::post('formulario-modulo', [
        FormularioModuloController::class,
        'store'
    ]);

    Route::delete('formulario-modulo/{id}', [
        FormularioModuloController::class,
        'destroy'
    ]);

    Route::get('formulario-modulo/modulo/{id_modulo}', [
        FormularioModuloController::class,
        'porModulo'
    ]);

    Route::get('modulo-rol', [
        ModuloRolController::class,
        'index'
    ]);

    Route::post('modulo-rol', [
        ModuloRolController::class,
        'store'
    ]);

    Route::delete('modulo-rol/{id}', [
        ModuloRolController::class,
        'destroy'
    ]);
    /*
    |--------------------------------------------------------------------------
    | Recursos Humanos
    |--------------------------------------------------------------------------
    */
    Route::prefix('recursos-humanos')->group(function () {

        Route::get('/usuarios', [
            RecursosHumanosController::class,
            'usuarios'
        ]);

        Route::put('/usuarios/{id}', [
            RecursosHumanosController::class,
            'actualizarUsuario'
        ]);

    });
    /*
    |--------------------------------------------------------------------------
    | Cuotas, matrícula y pagos
    |--------------------------------------------------------------------------
    */
    Route::get('/cuota/search', [
        CuotaController::class,
        'search'
    ])->name('cuota.search');

    Route::get('/cuota/estudiante/{id}', [
        CuotaController::class,
        'show'
    ])->name('cuota.estudiante.show');

    Route::get('/estudiantes/{id}/carreras', [
        CuotaController::class,
        'carreras'
    ]);

    Route::get('/estudiantes/{id}/carreras/{carreraId}/cuotas', [
        CuotaController::class,
        'cuotasPorCarrera'
    ]);

    Route::post('/matricula/generar', [
        MatriculaController::class,
        'generar'
    ])->name('matricula.generar');

    Route::post('/pagos', [
        PagoController::class,
        'store'
    ])->name('pagos.store');

    Route::get('/pagos', [
        PagoController::class,
        'index'
    ])->name('pagos.index');

    Route::prefix('empresa')->group(function () {
        Route::get('/', [
            EmpresaController::class,
            'show'
        ]);

        Route::patch('/', [
            EmpresaController::class,
            'update'
        ]);
    });
});