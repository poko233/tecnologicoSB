<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use App\Models\Modulo;
use App\Models\Formulario;
use App\Models\Permiso;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Crea datos de prueba: roles, módulos, formularios y permisos.
     */
    public function run(): void
    {
        // --- ROLES ---
        $admin = Rol::create([
            'rol'         => 'Admin',
            'descripcion' => 'Administrador del sistema',
        ]);

        $docente = Rol::create([
            'rol'         => 'Docente',
            'descripcion' => 'Docente de la institución',
        ]);

        $estudiante = Rol::create([
            'rol'         => 'Estudiante',
            'descripcion' => 'Estudiante registrado',
        ]);

        // --- MÓDULOS ---
        $configuraciones = Modulo::create([
            'modulo'      => 'Configuraciones',
            'descripcion' => 'Panel administrativo de configuración',
            'icono'       => 'fas fa-cog',
            'sidebar'     => true,
            'orden'       => 1,
        ]);

        $usuarios = Modulo::create([
            'modulo'      => 'Usuarios',
            'descripcion' => 'Gestión de usuarios del sistema',
            'icono'       => 'fas fa-users',
            'sidebar'     => true,
            'orden'       => 2,
        ]);

        $estudiantes = Modulo::create([
            'modulo'      => 'Estudiantes',
            'descripcion' => 'Gestión de estudiantes',
            'icono'       => 'fas fa-graduation-cap',
            'sidebar'     => true,
            'orden'       => 3,
        ]);

        // --- FORMULARIOS DEL MÓDULO "CONFIGURACIONES" ---
        $formRoles = Formulario::create([
            'formulario'  => 'Roles',
            'descripcion' => 'Gestión de roles',
            'ruta'        => '/api/roles',
            'componente'  => 'ConfiguracionesRoles',
            'icono'       => 'fas fa-shield-alt',
            'orden'       => 1,
        ]);

        $formModulos = Formulario::create([
            'formulario'  => 'Módulos',
            'descripcion' => 'Gestión de módulos del sistema',
            'ruta'        => '/api/modulos',
            'componente'  => 'ConfiguracionesModulos',
            'icono'       => 'fas fa-cube',
            'orden'       => 2,
        ]);

        $formFormularios = Formulario::create([
            'formulario'  => 'Formularios',
            'descripcion' => 'Gestión de formularios por módulo',
            'ruta'        => '/api/modulos/{id}/formularios',
            'componente'  => 'ConfiguracionesFormularios',
            'icono'       => 'fas fa-list',
            'orden'       => 3,
        ]);

        $formPermisos = Formulario::create([
            'formulario'  => 'Permisos',
            'descripcion' => 'Matriz de permisos CRUD por rol',
            'ruta'        => '/api/roles/{id}/permisos',
            'componente'  => 'ConfiguracionesPermisos',
            'icono'       => 'fas fa-lock',
            'orden'       => 4,
        ]);

        // --- FORMULARIOS DEL MÓDULO "USUARIOS" ---
        $formListaUsuarios = Formulario::create([
            'formulario'  => 'Lista de Usuarios',
            'descripcion' => 'Ver todos los usuarios',
            'ruta'        => '/api/users',
            'componente'  => 'UsuariosLista',
            'icono'       => 'fas fa-list',
            'orden'       => 1,
        ]);

        // --- FORMULARIOS DEL MÓDULO "ESTUDIANTES" ---
        $formListaEstudiantes = Formulario::create([
            'formulario'  => 'Lista de Estudiantes',
            'descripcion' => 'Ver todos los estudiantes',
            'ruta'        => '/api/students',
            'componente'  => 'EstudiantesLista',
            'icono'       => 'fas fa-list',
            'orden'       => 1,
        ]);

        // --- RELACIONES: formulario_modulo ---
        $configuraciones->formularios()->attach([
            $formRoles->id,
            $formModulos->id,
            $formFormularios->id,
            $formPermisos->id,
        ]);

        $usuarios->formularios()->attach($formListaUsuarios->id);
        $estudiantes->formularios()->attach($formListaEstudiantes->id);

        // --- PERMISOS: ADMIN (todas las acciones) ---
        // Configuraciones → todos los formularios
        Permiso::create([
            'id_rol'        => $admin->id,
            'id_formulario' => $formRoles->id,
            'ver'           => true,
            'crear'         => true,
            'editar'        => true,
            'eliminar'      => true,
        ]);

        Permiso::create([
            'id_rol'        => $admin->id,
            'id_formulario' => $formModulos->id,
            'ver'           => true,
            'crear'         => true,
            'editar'        => true,
            'eliminar'      => true,
        ]);

        Permiso::create([
            'id_rol'        => $admin->id,
            'id_formulario' => $formFormularios->id,
            'ver'           => true,
            'crear'         => true,
            'editar'        => true,
            'eliminar'      => true,
        ]);

        Permiso::create([
            'id_rol'        => $admin->id,
            'id_formulario' => $formPermisos->id,
            'ver'           => true,
            'crear'         => true,
            'editar'        => true,
            'eliminar'      => true,
        ]);

        // Usuarios → ver, crear, editar
        Permiso::create([
            'id_rol'        => $admin->id,
            'id_formulario' => $formListaUsuarios->id,
            'ver'           => true,
            'crear'         => true,
            'editar'        => true,
            'eliminar'      => true,
        ]);

        // Estudiantes → ver, crear, editar
        Permiso::create([
            'id_rol'        => $admin->id,
            'id_formulario' => $formListaEstudiantes->id,
            'ver'           => true,
            'crear'         => true,
            'editar'        => true,
            'eliminar'      => true,
        ]);

        // --- PERMISOS: DOCENTE (solo ver Estudiantes) ---
        Permiso::create([
            'id_rol'        => $docente->id,
            'id_formulario' => $formListaEstudiantes->id,
            'ver'           => true,
            'crear'         => false,
            'editar'        => false,
            'eliminar'      => false,
        ]);

        // --- PERMISOS: ESTUDIANTE (sin acceso a nada) ---

        // --- USUARIO DE PRUEBA ---
        $user = User::create([
            'usuario'    => 'admin',
            'password'   => bcrypt('password123'),
            'ci'         => '123456789',
            'nombres'    => 'Admin',
            'apellidos'  => 'System',
            'genero'     => 'MASCULINO',
            'fecha_nac'  => '1990-01-01',
            'email'      => 'admin@test.com',
            'estado'     => 'ACTIVO',
        ]);

        // Asignar rol Admin al usuario
        $user->roles()->attach($admin->id);

        // Crear usuario Docente de prueba
        $userDocente = User::create([
            'usuario'    => 'docente',
            'password'   => bcrypt('password123'),
            'ci'         => '987654321',
            'nombres'    => 'Docente',
            'apellidos'  => 'Test',
            'genero'     => 'FEMENINO',
            'fecha_nac'  => '1985-05-15',
            'email'      => 'docente@test.com',
            'estado'     => 'ACTIVO',
        ]);

        $userDocente->roles()->attach($docente->id);

        echo "\n✅ Seeder completado:\n";
        echo "  👤 Usuario Admin: usuario=admin, password=password123\n";
        echo "  👤 Usuario Docente: usuario=docente, password=password123\n";
        echo "  📦 3 roles creados\n";
        echo "  📁 3 módulos creados\n";
        echo "  📋 7 formularios creados\n";
        echo "  🔐 Permisos configurados\n";
    }
}
