<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CarreraUsuario;
use App\Models\Inscripcion;
use App\Models\DocumentoEstudiante;
use Illuminate\Http\Request;

class ResumenInscripcionController extends Controller
{
    public function show($idUsuario)
    {
        $usuario = User::where('id', $idUsuario)->firstOrFail();

        $carreraUsuario = CarreraUsuario::with('carrera')
            ->where('idUsuario', $idUsuario)
            ->latest('idCarreraUsuario')
            ->first();

        $inscripcion = Inscripcion::with('grupo')
            ->where('idUsuario', $idUsuario)
            ->latest('idInscripcion')
            ->first();

        $documentos = DocumentoEstudiante::where('idUsuario', $idUsuario)->get();

        return response()->json([
            'usuario' => [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidoPaterno' => $usuario->apellidoPaterno,
                'apellidoMaterno' => $usuario->apellidoMaterno,
                'ci' => $usuario->ci,
                'celular' => $usuario->celular,
                'direccion' => $usuario->direccion,
            ],
            'carrera' => $carreraUsuario?->carrera,
            'grupo' => $inscripcion?->grupo,
            'documentos' => $documentos,
            'validacion' => [
                'datosPersonales' => true,
                'datosAcademicos' => $carreraUsuario && $inscripcion,
                'documentosCargados' => $documentos->count() > 0,
            ],
        ]);
    }

    public function finalizar($idUsuario)
    {
        $usuario = User::where('id', $idUsuario)->firstOrFail();

        return response()->json([
            'message' => 'Inscripción finalizada correctamente',
            'idUsuario' => $usuario->id,
        ]);
    }
}