<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EstudianteController extends Controller
{
    public function index()
    {
        return response()->json([
            'estudiantes' => User::with(['numeroReferencias', 'roles'])
                ->whereHas('roles', function ($query) {
                    $query->where('rol.id', 2);
                })
                ->latest('id')
                ->get()
        ]);
    }

    public function continuarInscripcion()
    {
        $estudiantes = User::with(['numeroReferencias', 'roles'])
            ->whereHas('roles', function ($query) {
                $query->where('rol.id', 2);
            })
            ->whereDoesntHave('cuotas')
            ->whereDoesntHave('carreras')
            ->latest('id')
            ->get();

        return response()->json([
            'estudiantes' => $estudiantes
        ]);
    }

    public function documentosInscripcion(string $id)
    {
        $documentos = DB::table('DocumentoEstudiante')
            ->select(
                'idDocumentoEstudiante',
                'nombreDocumento',
                'ubicacionArchivo',
                'estadoDocumento',
                'idUsuario'
            )
            ->where('idUsuario', $id)
            ->orderBy('idDocumentoEstudiante')
            ->get();

        return response()->json([
            'documentos' => $documentos
        ]);
    }

    public function verificarDatos(Request $request)
    {
        $validated = $request->validate([
            'carnet' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'idUsuario' => 'nullable|integer',
        ]);

        $idUsuario = $validated['idUsuario'] ?? null;

        $carnetExiste = false;
        $correoExiste = false;

        if (!empty($validated['carnet'])) {
            $carnetExiste = User::where('ci', $validated['carnet'])
                ->when($idUsuario, function ($query) use ($idUsuario) {
                    $query->where('id', '!=', $idUsuario);
                })
                ->exists();
        }

        if (!empty($validated['email'])) {
            $correoExiste = User::where('email', strtolower($validated['email']))
                ->when($idUsuario, function ($query) use ($idUsuario) {
                    $query->where('id', '!=', $idUsuario);
                })
                ->exists();
        }

        return response()->json([
            'carnetExiste' => $carnetExiste,
            'correoExiste' => $correoExiste,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'apellidoPaterno' => 'required|string|max:50',
            'apellidoMaterno' => 'nullable|string|max:50',
            'nombres' => 'required|string|max:50',
            'genero' => 'required|string|in:MASCULINO,FEMENINO',
            'carnet' => 'required|string|max:50|unique:user,ci',
            'email' => 'required|email|max:100|unique:user,email',
            'expedidoEn' => 'required|string|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',
            'fechaNacimiento' => 'required|date',
            'direccion' => 'required|string|max:100',
            'celular' => 'required|string|max:20',
            'referenciaNombre' => 'required|string|max:50',
            'referenciaParentesco' => 'required|string|max:50',
            'referenciaNumero' => 'required|string|max:50',
        ], [
            'carnet.unique' => 'El carnet ya está registrado.',
            'email.unique' => 'El correo ya está registrado.',
            'email.email' => 'El correo no tiene un formato válido.',
        ]);

        DB::beginTransaction();

        try {
            $estudiante = User::create([
                'usuario' => $validated['carnet'],
                'password' => Hash::make($validated['carnet']),
                'ci' => $validated['carnet'],
                'email' => strtolower($validated['email']),
                'expedido' => $validated['expedidoEn'],
                'apellidoPaterno' => $validated['apellidoPaterno'],
                'apellidoMaterno' => $validated['apellidoMaterno'] ?? '',
                'nombres' => $validated['nombres'],
                'genero' => $validated['genero'],
                'fecha_nac' => $validated['fechaNacimiento'],
                'direccion' => $validated['direccion'],
                'celular' => $validated['celular'],
                'estado' => 'activo',
                'verificacion' => 0,
            ]);

            $estudiante->roles()->syncWithoutDetaching([2]);

            $estudiante->numeroReferencias()->create([
                'nombreContactoReferencia' => $validated['referenciaNombre'],
                'parentesco' => $validated['referenciaParentesco'],
                'numeroReferencia' => $validated['referenciaNumero'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Estudiante registrado correctamente',
                'estudiante' => $estudiante->load(['numeroReferencias', 'roles'])
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'No se pudo registrar al estudiante.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $estudiante = User::with(['numeroReferencias', 'roles'])
            ->whereHas('roles', function ($query) {
                $query->where('rol.id', 2);
            })
            ->findOrFail($id);

        return response()->json([
            'estudiante' => $estudiante
        ]);
    }

    public function update(Request $request, string $id)
    {
        $estudiante = User::findOrFail($id);

        $validated = $request->validate([
            'apellidoPaterno' => 'required|string|max:50',
            'apellidoMaterno' => 'nullable|string|max:50',
            'nombres' => 'required|string|max:50',
            'genero' => 'required|string|in:MASCULINO,FEMENINO',

            'carnet' => [
                'required',
                'string',
                'max:50',
                Rule::unique('user', 'ci')->ignore($id, 'id'),
            ],

            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('user', 'email')->ignore($id, 'id'),
            ],

            'expedidoEn' => 'required|string|in:LPZ,CBBA,OR,PT,TJ,SCZ,BN,PD,CH,QR,EXT',
            'fechaNacimiento' => 'required|date',
            'direccion' => 'required|string|max:100',
            'celular' => 'required|string|max:20',
            'referenciaNombre' => 'required|string|max:50',
            'referenciaParentesco' => 'required|string|max:50',
            'referenciaNumero' => 'required|string|max:50',
        ], [
            'carnet.unique' => 'El carnet ya está registrado.',
            'email.unique' => 'El correo ya está registrado.',
            'email.email' => 'El correo no tiene un formato válido.',
        ]);

        DB::beginTransaction();

        try {
            $estudiante->update([
                'usuario' => $validated['carnet'],
                'ci' => $validated['carnet'],
                'email' => strtolower($validated['email']),
                'expedido' => $validated['expedidoEn'],
                'apellidoPaterno' => $validated['apellidoPaterno'],
                'apellidoMaterno' => $validated['apellidoMaterno'] ?? '',
                'nombres' => $validated['nombres'],
                'genero' => $validated['genero'],
                'fecha_nac' => $validated['fechaNacimiento'],
                'direccion' => $validated['direccion'],
                'celular' => $validated['celular'],
            ]);

            $estudiante->roles()->syncWithoutDetaching([2]);

            $estudiante->numeroReferencias()->updateOrCreate(
                ['idUsuario' => $estudiante->id],
                [
                    'nombreContactoReferencia' => $validated['referenciaNombre'],
                    'parentesco' => $validated['referenciaParentesco'],
                    'numeroReferencia' => $validated['referenciaNumero'],
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Estudiante actualizado correctamente',
                'estudiante' => $estudiante->load(['numeroReferencias', 'roles'])
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'No se pudo actualizar el estudiante.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $estudiante = User::findOrFail($id);

        DB::beginTransaction();

        try {
            $estudiante->numeroReferencias()->delete();
            $estudiante->roles()->detach();
            $estudiante->delete();

            DB::commit();

            return response()->json([
                'message' => 'Estudiante eliminado'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'No se pudo eliminar el estudiante.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}