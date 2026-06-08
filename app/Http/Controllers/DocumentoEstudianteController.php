<?php

namespace App\Http\Controllers;

use App\Models\DocumentoEstudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DocumentoEstudianteController extends Controller
{
    private function archivoUrl(?string $ruta): ?string
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'idUsuario' => 'required|exists:user,id',
            'nombreDocumento' => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $archivo = $request->file('archivo');

        $nombreArchivo = uniqid() . '_' . time() . '.' . $archivo->getClientOriginalExtension();

        $destino = public_path('documentos-estudiantes');

        if (!file_exists($destino)) {
            mkdir($destino, 0777, true);
        }

        $archivo->move($destino, $nombreArchivo);

        $ruta = 'documentos-estudiantes/' . $nombreArchivo;

        $documentoExistente = DocumentoEstudiante::where('idUsuario', $validated['idUsuario'])
            ->where('nombreDocumento', $validated['nombreDocumento'])
            ->latest('idDocumentoEstudiante')
            ->first();

        if ($documentoExistente) {
            if (
                $documentoExistente->ubicacionArchivo &&
                File::exists(public_path($documentoExistente->ubicacionArchivo))
            ) {
                File::delete(public_path($documentoExistente->ubicacionArchivo));
            }

            DocumentoEstudiante::where('idUsuario', $validated['idUsuario'])
                ->where('nombreDocumento', $validated['nombreDocumento'])
                ->where('idDocumentoEstudiante', '!=', $documentoExistente->idDocumentoEstudiante)
                ->delete();

            $documentoExistente->update([
                'ubicacionArchivo' => $ruta,
                'estadoDocumento' => 'Entregado',
            ]);

            $documento = $documentoExistente->fresh();
            $documento->url = $this->archivoUrl($documento->ubicacionArchivo);

            return response()->json([
                'message' => 'Documento actualizado correctamente',
                'documento' => $documento,
                'url' => $documento->url,
            ], 200);
        }

        $documento = DocumentoEstudiante::create([
            'nombreDocumento' => $validated['nombreDocumento'],
            'ubicacionArchivo' => $ruta,
            'estadoDocumento' => 'Entregado',
            'idUsuario' => $validated['idUsuario'],
        ]);

        $documento->url = $this->archivoUrl($documento->ubicacionArchivo);

        return response()->json([
            'message' => 'Documento subido correctamente',
            'documento' => $documento,
            'url' => $documento->url,
        ], 201);
    }

    public function documentosUsuario($idUsuario)
    {
        $documentos = DocumentoEstudiante::where('idUsuario', $idUsuario)
            ->orderBy('nombreDocumento')
            ->latest('idDocumentoEstudiante')
            ->get()
            ->unique('nombreDocumento')
            ->values()
            ->map(function ($documento) {
                $documento->url = $this->archivoUrl($documento->ubicacionArchivo);
                return $documento;
            });

        return response()->json([
            'documentos' => $documentos,
        ]);
    }    
}