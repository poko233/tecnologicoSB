<?php

namespace App\Http\Controllers;

use App\Models\CarreraUsuario;
use App\Models\Cuota;
use App\Models\Inscripcion;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscripcionAcademicaController extends Controller
{
    public function inscribir(Request $request)
    {
        $validated = $request->validate([
            'idUsuario' => 'required|exists:user,id',
            'idCarrera' => 'required|exists:Carrera,idCarrera',
            'idGrupo' => 'required|exists:Grupo,idGrupo',
        ]);

        return DB::transaction(function () use ($validated) {
            $existeGrupo = Inscripcion::where('idUsuario', $validated['idUsuario'])
                ->where('idGrupo', $validated['idGrupo'])
                ->exists();

            if ($existeGrupo) {
                return response()->json([
                    'message' => 'El estudiante ya está inscrito en este grupo'
                ], 422);
            }

            CarreraUsuario::firstOrCreate([
                'idUsuario' => $validated['idUsuario'],
                'idCarrera' => $validated['idCarrera'],
            ]);

            $inscripcion = Inscripcion::create([
                'idUsuario' => $validated['idUsuario'],
                'idGrupo' => $validated['idGrupo'],
            ]);

            $carrera = Carrera::findOrFail($validated['idCarrera']);

            if ($carrera->numeroCuotas && $carrera->cuotaMes) {
                for ($i = 1; $i <= $carrera->numeroCuotas; $i++) {
                    Cuota::create([
                        'monto' => $carrera->cuotaMes,
                        'numeroCuota' => $i,
                        'descuento' => 0,
                        'estadoCuota' => 'debe',
                        'idUsuario' => $validated['idUsuario'],
                    ]);
                }
            }

            return response()->json([
                'message' => 'Estudiante inscrito correctamente al grupo',
                'inscripcion' => $inscripcion,
            ], 201);
        });
    }
}