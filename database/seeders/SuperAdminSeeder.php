<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = [
            ['id' => 1, 'rol' => 'Personal',                'descripcion' => 'Encargado de tareas administrativas generales del instituto'],
            ['id' => 2, 'rol' => 'Estudiante',              'descripcion' => 'Persona inscrita en una carrera o curso del instituto'],
            ['id' => 3, 'rol' => 'Administrador',           'descripcion' => 'Gestiona usuarios, roles y configuraciones del sistema'],
            ['id' => 4, 'rol' => 'Rector',                  'descripcion' => 'Autoridad máxima académica y administrativa del instituto'],
            ['id' => 5, 'rol' => 'Director Administrativo', 'descripcion' => 'Responsable de la gestión administrativa y financiera'],
            ['id' => 6, 'rol' => 'Director Academico',      'descripcion' => 'Responsable de la planificación y control académico'],
            ['id' => 7, 'rol' => 'Fundador',                'descripcion' => 'Acceso total al sistema, rol reservado para el fundador del sistema'],
        ];

        foreach ($roles as $rol) {
            DB::table('rol')->updateOrInsert(
                ['id' => $rol['id']],
                array_merge($rol, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Super Admin en tabla user
        $userId = DB::table('user')->insertGetId([
            'usuario'         => 'admin',
            'password'        => Hash::make('admin123'),
            'ci'              => '00000000',
            'nombres'         => 'Super',
            'apellidoPaterno' => 'Admin',
            'apellidoMaterno' => 'Fundador',
            'genero'          => 'MASCULINO',
            'fecha_nac'       => '1990-01-01',
            'estado'          => 'ACTIVO',
            'verificacion'    => '1',
            'expedido'        => 'CBBA',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Asignar todos los roles al super admin
        foreach ($roles as $rol) {
            DB::table('user_rol')->insert([
                'id_user'    => $userId,
                'id_rol'     => $rol['id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}