<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DocenteController extends Controller
{
    public function index()
    {
        $docentes = Docente::with(['usuario.roles'])
            ->orderByDesc('idDocente')
            ->get();

        return response()->json([
            'docentes' => $docentes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ci' => 'required|string|max:12|unique:user,ci',
            'expedido' => 'required|string|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',

            'nombres' => 'required|string|max:40',
            'apellidoPaterno' => 'required|string|max:50',
            'apellidoMaterno' => 'nullable|string|max:50',

            'genero' => 'required|string|in:MASCULINO,FEMENINO',
            'fecha_nac' => 'required|date',

            'email' => 'nullable|email|max:80|unique:user,email',
            'celular' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:50',

            'profesion' => 'required|string|max:50',
            'abreviaturaProfesional' => 'nullable|string|max:50',
        ]);

        $docente = DB::transaction(function () use ($validated) {
            $usuario = User::create([
                'usuario' => $validated['ci'],
                'password' => Hash::make($validated['ci']),

                'ci' => $validated['ci'],
                'expedido' => $validated['expedido'],

                'nombres' => $validated['nombres'],
                'apellidoPaterno' => $validated['apellidoPaterno'],
                'apellidoMaterno' => $validated['apellidoMaterno'] ?? null,

                'genero' => $validated['genero'],
                'fecha_nac' => $validated['fecha_nac'],

                'email' => $validated['email'] ?? null,
                'celular' => $validated['celular'] ?? null,
                'direccion' => $validated['direccion'] ?? null,

                'estado' => 'ACTIVO',
                'verificacion' => 0,
            ]);

            // =========================
            // ASIGNAR ROL DOCENTE
            // =========================
            // Rol Docente = ID 3
            $usuario->roles()->syncWithoutDetaching([3]);

            Docente::create([
                'idDocente' => $usuario->id,
                'profesion' => $validated['profesion'],
                'abreviaturaProfesional' => $validated['abreviaturaProfesional'] ?? null,
                'fechaRegistro' => now()->toDateString(),
                'estadoDocente' => 'activo',
            ]);

            return Docente::with(['usuario.roles'])->findOrFail($usuario->id);
        });

        return response()->json([
            'message' => 'Docente registrado correctamente',
            'docente' => $docente,
        ], 201);
    }

    public function update(Request $request, $idDocente)
    {
        $docente = Docente::with('usuario')->findOrFail($idDocente);
        $usuario = $docente->usuario;

        $validated = $request->validate([
            'ci' => [
                'required',
                'string',
                'max:12',
                Rule::unique('user', 'ci')->ignore($usuario->id, 'id'),
            ],
            'expedido' => 'required|string|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',

            'nombres' => 'required|string|max:40',
            'apellidoPaterno' => 'required|string|max:50',
            'apellidoMaterno' => 'nullable|string|max:50',

            'genero' => 'required|string|in:MASCULINO,FEMENINO',
            'fecha_nac' => 'required|date',

            'email' => [
                'nullable',
                'email',
                'max:80',
                Rule::unique('user', 'email')->ignore($usuario->id, 'id'),
            ],
            'celular' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:50',

            'estado' => 'required|string|in:ACTIVO,INACTIVO',

            'profesion' => 'required|string|max:50',
            'abreviaturaProfesional' => 'nullable|string|max:50',
            'estadoDocente' => 'required|string|in:activo,inactivo',
        ]);

        DB::transaction(function () use ($usuario, $docente, $validated) {
            $usuario->update([
                'usuario' => $validated['ci'],
                'ci' => $validated['ci'],
                'expedido' => $validated['expedido'],

                'nombres' => $validated['nombres'],
                'apellidoPaterno' => $validated['apellidoPaterno'],
                'apellidoMaterno' => $validated['apellidoMaterno'] ?? null,

                'genero' => $validated['genero'],
                'fecha_nac' => $validated['fecha_nac'],

                'email' => $validated['email'] ?? null,
                'celular' => $validated['celular'] ?? null,
                'direccion' => $validated['direccion'] ?? null,

                'estado' => $validated['estado'],
            ]);

            // Asegura que siga teniendo rol Docente.
            $usuario->roles()->syncWithoutDetaching([3]);

            $docente->update([
                'profesion' => $validated['profesion'],
                'abreviaturaProfesional' => $validated['abreviaturaProfesional'] ?? null,
                'estadoDocente' => $validated['estadoDocente'],
            ]);
        });

        return response()->json([
            'message' => 'Docente actualizado correctamente',
            'docente' => Docente::with(['usuario.roles'])->findOrFail($idDocente),
        ]);
    }

    public function destroy($idDocente)
    {
        $docente = Docente::with('usuario')->findOrFail($idDocente);

        DB::transaction(function () use ($docente) {
            $docente->update([
                'estadoDocente' => 'inactivo',
            ]);

            if ($docente->usuario) {
                $docente->usuario->update([
                    'estado' => 'INACTIVO',
                ]);
            }
        });

        return response()->json([
            'message' => 'Docente desactivado correctamente',
        ]);
    }

    public function activar($idDocente)
    {
        $docente = Docente::with('usuario')->findOrFail($idDocente);

        DB::transaction(function () use ($docente) {
            $docente->update([
                'estadoDocente' => 'activo',
            ]);

            if ($docente->usuario) {
                $docente->usuario->update([
                    'estado' => 'ACTIVO',
                ]);

                $docente->usuario->roles()->syncWithoutDetaching([3]);
            }
        });

        return response()->json([
            'message' => 'Docente activado correctamente',
        ]);
    }
}