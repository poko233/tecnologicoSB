<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EstudianteController extends Controller
{
    public function index()
    {
        return response()->json([
            'estudiantes' => User::with(['numeroReferencias', 'roles'])
                ->latest('id')
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'apellidoPaterno' => 'required|string|max:50',
            'apellidoMaterno' => 'required|string|max:50',
            'nombres' => 'required|string|max:50',

            'genero' => 'required|string|in:MASCULINO,FEMENINO',

            'carnet' => 'required|string|max:50|unique:user,ci',

            'expedidoEn' => 'required|string|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',

            'fechaNacimiento' => 'required|date',

            'direccion' => 'required|string|max:50',

            'celular' => 'required|string|max:20',

            'referenciaNombre' => 'required|string|max:50',
            'referenciaParentesco' => 'required|string|max:50',
            'referenciaNumero' => 'required|string|max:50',
        ]);

        $estudiante = User::create([
            'usuario' => $validated['carnet'],
            'password' => Hash::make($validated['carnet']),

            'ci' => $validated['carnet'],
            'expedido' => $validated['expedidoEn'],

            'apellidoPaterno' => $validated['apellidoPaterno'],
            'apellidoMaterno' => $validated['apellidoMaterno'],
            'nombres' => $validated['nombres'],

            'genero' => $validated['genero'],
            'fecha_nac' => $validated['fechaNacimiento'],

            'direccion' => $validated['direccion'],
            'celular' => $validated['celular'],

            'estado' => 'activo',
            'verificacion' => 0,
        ]);

        // =========================
        // ASIGNAR ROL ESTUDIANTE
        // =========================
        // Rol Estudiante = ID 2
        $estudiante->roles()->syncWithoutDetaching([2]);

        // =========================
        // GUARDAR REFERENCIA
        // =========================
        $estudiante->numeroReferencias()->create([
            'nombreContactoReferencia' => $validated['referenciaNombre'],
            'parentesco' => $validated['referenciaParentesco'],
            'numeroReferencia' => $validated['referenciaNumero'],
        ]);

        return response()->json([
            'message' => 'Estudiante registrado correctamente',
            'estudiante' => $estudiante->load([
                'numeroReferencias',
                'roles'
            ])
        ], 201);
    }

    public function show(string $id)
    {
        $estudiante = User::with([
            'numeroReferencias',
            'roles'
        ])->findOrFail($id);

        return response()->json([
            'estudiante' => $estudiante
        ]);
    }

    public function update(Request $request, string $id)
    {
        $estudiante = User::findOrFail($id);

        $validated = $request->validate([
            'apellidoPaterno' => 'sometimes|required|string|max:50',
            'apellidoMaterno' => 'sometimes|required|string|max:50',
            'nombres' => 'sometimes|required|string|max:50',

            'genero' => 'sometimes|required|string|in:MASCULINO,FEMENINO',

            'carnet' => 'sometimes|required|string|max:50|unique:user,ci,' . $id . ',id',

            'expedidoEn' => 'sometimes|required|string|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',

            'fechaNacimiento' => 'sometimes|required|date',

            'direccion' => 'sometimes|required|string|max:50',

            'celular' => 'sometimes|required|string|max:20',

            'referenciaNombre' => 'sometimes|required|string|max:50',
            'referenciaParentesco' => 'sometimes|required|string|max:50',
            'referenciaNumero' => 'sometimes|required|string|max:50',
        ]);

        $dataUsuario = [];

        if (isset($validated['carnet'])) {
            $dataUsuario['ci'] = $validated['carnet'];
            $dataUsuario['usuario'] = $validated['carnet'];
        }

        if (isset($validated['expedidoEn'])) {
            $dataUsuario['expedido'] = $validated['expedidoEn'];
        }

        if (isset($validated['apellidoPaterno'])) {
            $dataUsuario['apellidoPaterno'] = $validated['apellidoPaterno'];
        }

        if (isset($validated['apellidoMaterno'])) {
            $dataUsuario['apellidoMaterno'] = $validated['apellidoMaterno'];
        }

        if (isset($validated['nombres'])) {
            $dataUsuario['nombres'] = $validated['nombres'];
        }

        if (isset($validated['genero'])) {
            $dataUsuario['genero'] = $validated['genero'];
        }

        if (isset($validated['fechaNacimiento'])) {
            $dataUsuario['fecha_nac'] = $validated['fechaNacimiento'];
        }

        if (isset($validated['direccion'])) {
            $dataUsuario['direccion'] = $validated['direccion'];
        }

        if (isset($validated['celular'])) {
            $dataUsuario['celular'] = $validated['celular'];
        }

        $estudiante->update($dataUsuario);

        if (
            isset($validated['referenciaNombre']) ||
            isset($validated['referenciaParentesco']) ||
            isset($validated['referenciaNumero'])
        ) {
            $estudiante->numeroReferencias()->updateOrCreate(
                ['idUsuario' => $estudiante->id],
                [
                    'nombreContactoReferencia' => $validated['referenciaNombre'] ?? null,
                    'parentesco' => $validated['referenciaParentesco'] ?? null,
                    'numeroReferencia' => $validated['referenciaNumero'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Estudiante actualizado',
            'estudiante' => $estudiante->load([
                'numeroReferencias',
                'roles'
            ])
        ]);
    }

    public function destroy(string $id)
    {
        $estudiante = User::findOrFail($id);

        $estudiante->numeroReferencias()->delete();

        // Eliminar relaciones de roles
        $estudiante->roles()->detach();

        $estudiante->delete();

        return response()->json([
            'message' => 'Estudiante eliminado'
        ]);
    }
}