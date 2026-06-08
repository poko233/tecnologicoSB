<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecursosHumanosController extends Controller
{
    public function usuarios()
    {
        try {
            $usuarios = User::with(['roles', 'numeroReferencias'])
                ->latest('id')
                ->get()
                ->map(function ($usuario) {
                    $usuario->esEstudiante = $usuario->roles
                        ->contains(function ($rol) {
                            return strtolower($rol->rol) === 'estudiante';
                        });

                    return $usuario;
                });

            return response()->json([
                'usuarios' => $usuarios,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error en RecursosHumanosController al cargar usuarios.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    public function actualizarUsuario(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'usuario' => [
                    'required',
                    'string',
                    'max:40',
                    Rule::unique('user', 'usuario')->ignore($user->id, 'id'),
                ],
                'ci' => [
                    'required',
                    'string',
                    'max:12',
                    Rule::unique('user', 'ci')->ignore($user->id, 'id'),
                ],
                'nombres' => 'required|string|max:40',
                'apellidoPaterno' => 'required|string|max:50',
                'apellidoMaterno' => 'nullable|string|max:50',
                'genero' => 'required|in:MASCULINO,FEMENINO',
                'fecha_nac' => 'nullable|date',
                'email' => [
                    'nullable',
                    'email',
                    'max:80',
                    Rule::unique('user', 'email')->ignore($user->id, 'id'),
                ],
                'telefono' => 'nullable|string|max:10',
                'celular' => 'nullable|string|max:20',
                'direccion' => 'nullable|string|max:50',
                'expedido' => 'nullable|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',
                'estado' => 'required|in:ACTIVO,INACTIVO',
            ]);

            $user->update($validated);

            $user->load(['roles', 'numeroReferencias']);

            $user->esEstudiante = $user->roles
                ->contains(function ($rol) {
                    return strtolower($rol->rol) === 'estudiante';
                });

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'usuario' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error en RecursosHumanosController al actualizar usuario.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
}