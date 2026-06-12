<?php

namespace App\Http\Controllers;

use App\Models\ObservacionUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class RecursosHumanosController extends Controller
{
    private function documentoUrl(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        $base = rtrim(env('FILES_PUBLIC_URL', url('/')), '/');

        return $base . '/' . ltrim($ruta, '/');
    }

    private function fotoUrl(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        if (str_starts_with($ruta, 'data:image')) {
            return $ruta;
        }

        $base = rtrim(env('FOTOS_PUBLIC_URL', url('/')), '/');

        return $base . '/' . ltrim($ruta, '/');
    }

    private function qrUrl(?string $qr): ?string
    {
        if (!$qr) {
            return null;
        }

        $qr = trim($qr);

        if ($qr === '') {
            return null;
        }

        if (str_starts_with($qr, 'data:image')) {
            return $qr;
        }

        if (str_starts_with($qr, 'http')) {
            return $qr;
        }

        if (
            str_starts_with($qr, 'qr/') ||
            str_starts_with($qr, 'qrs/') ||
            str_starts_with($qr, 'codigos-qr/') ||
            str_starts_with($qr, 'codigo-qr/')
        ) {
            $base = rtrim(env('FOTOS_PUBLIC_URL', url('/')), '/');

            return $base . '/' . ltrim($qr, '/');
        }

        return 'data:image/png;base64,' . $qr;
    }

    private function obtenerObservacionPromociones(int $userId): ?string
    {
        return ObservacionUsuario::where('user_id', $userId)
            ->where('tipo', 'PROMOCION_REGALO')
            ->latest('id')
            ->value('descripcion');
    }

    private function prepararUsuario(User $usuario): User
    {
        $usuario->esEstudiante = $usuario->roles->contains(function ($rol) {
            return strtolower($rol->rol) === 'estudiante';
        });

        $usuario->fotoUrl = $this->fotoUrl($usuario->foto);
        $usuario->qrUrl = $this->qrUrl($usuario->codigo_qr);

        $usuario->observacionPromociones = $this->obtenerObservacionPromociones(
            $usuario->id
        );

        if ($usuario->relationLoaded('documentos')) {
            $usuario->documentos->transform(function ($documento) {
                $documento->archivoUrl = $this->documentoUrl(
                    $documento->ubicacionArchivo
                );

                return $documento;
            });
        }

        return $usuario;
    }

    public function usuarios()
    {
        try {
            $usuarios = User::with([
                    'roles',
                    'numeroReferencias',
                    'documentos',
                ])
                ->latest('id')
                ->get()
                ->map(function ($usuario) {
                    return $this->prepararUsuario($usuario);
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

    public function usuarioDetalle(string $id)
    {
        try {
            $usuario = User::with([
                    'roles',
                    'numeroReferencias',
                    'documentos',
                ])
                ->findOrFail($id);

            return response()->json([
                'usuario' => $this->prepararUsuario($usuario),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al cargar detalle del usuario.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    public function actualizarUsuario(Request $request, string $id)
    {
        try {
            $user = User::with('roles')->findOrFail($id);

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
                'apellidoPaterno' => 'nullable|string|max:50',
                'apellidoMaterno' => 'nullable|string|max:50',
                'genero' => 'required|in:MASCULINO,FEMENINO',
                'fecha_nac' => 'required|date',
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

                'referenciaNombre' => 'nullable|string|max:50',
                'referenciaParentesco' => 'nullable|string|max:50',
                'referenciaNumero' => 'nullable|string|max:50',
            ]);

            $datosUsuario = collect($validated)
                ->except([
                    'referenciaNombre',
                    'referenciaParentesco',
                    'referenciaNumero',
                ])
                ->toArray();

            $user->update($datosUsuario);

            $esEstudiante = $user->roles->contains(function ($rol) {
                return strtolower($rol->rol) === 'estudiante';
            });

            if ($esEstudiante) {
                $user->numeroReferencias()->updateOrCreate(
                    [
                        'idUsuario' => $user->id,
                    ],
                    [
                        'nombreContactoReferencia' => $request->referenciaNombre,
                        'parentesco' => $request->referenciaParentesco,
                        'numeroReferencia' => $request->referenciaNumero,
                    ]
                );
            }

            $user->load([
                'roles',
                'numeroReferencias',
                'documentos',
            ]);

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'usuario' => $this->prepararUsuario($user),
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

    public function actualizarFoto(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'foto' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            ]);

            if ($user->foto && !str_starts_with($user->foto, 'http')) {
                $rutaAnterior = public_path($user->foto);

                if (File::exists($rutaAnterior)) {
                    File::delete($rutaAnterior);
                }
            }

            $archivo = $request->file('foto');

            $nombreArchivo =
                uniqid() . '_' .
                time() . '.' .
                $archivo->getClientOriginalExtension();

            $carpeta = public_path('fotos-usuarios');

            if (!File::exists($carpeta)) {
                File::makeDirectory($carpeta, 0755, true);
            }

            $archivo->move($carpeta, $nombreArchivo);

            $user->foto = 'fotos-usuarios/' . $nombreArchivo;
            $user->save();

            $user->load([
                'roles',
                'numeroReferencias',
                'documentos',
            ]);

            return response()->json([
                'message' => 'Foto actualizada correctamente.',
                'usuario' => $this->prepararUsuario($user),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar la foto.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
}