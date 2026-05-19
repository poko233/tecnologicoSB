<?php

namespace App\Http\Controllers;

use App\Models\DocumentoEstudiante;
use Illuminate\Http\Request;

class DocumentoEstudianteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'idUsuario' => 'required|exists:user,id',
            'nombreDocumento' => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $archivo = $request->file('archivo');

        $nombreArchivo =
            uniqid() . '_' .
            time() . '.' .
            $archivo->getClientOriginalExtension();

        $destino = public_path('documentos-estudiantes');

        if (!file_exists($destino)) {
            mkdir($destino, 0777, true);
        }

        $archivo->move($destino, $nombreArchivo);

        $ruta = 'documentos-estudiantes/' . $nombreArchivo;

        $documento = DocumentoEstudiante::create([
            'nombreDocumento' => $validated['nombreDocumento'],
            'ubicacionArchivo' => $ruta,
            'estadoDocumento' => 'Entregado',
            'idUsuario' => $validated['idUsuario'],
        ]);

        return response()->json([
            'message' => 'Documento subido correctamente',
            'documento' => $documento,
            'url' => asset($ruta),
        ], 201);
    }

    public function documentosUsuario($idUsuario)
    {
        return response()->json([
            'documentos' => DocumentoEstudiante::where('idUsuario', $idUsuario)->get()
        ]);
    }
}