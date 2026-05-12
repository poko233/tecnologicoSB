<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModuloResource;
use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index()
    {
        $modulos = Modulo::with('formularios')->get();
        return ModuloResource::collection($modulos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'modulo'      => 'required|string|max:40|unique:modulo,modulo',
            'descripcion' => 'nullable|string',
            'icono'       => 'nullable|string',
        ]);

        $modulo = Modulo::create($request->only('modulo', 'descripcion', 'icono'));

        return (new ModuloResource($modulo))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Modulo $modulo)
    {
        return new ModuloResource($modulo->load('formularios'));
    }

    public function update(Request $request, Modulo $modulo)
    {
        $request->validate([
            'modulo'      => 'required|string|max:40|unique:modulo,modulo,' . $modulo->id,
            'descripcion' => 'nullable|string',
            'icono'       => 'nullable|string',
        ]);

        $modulo->update($request->only('modulo', 'descripcion', 'icono'));

        return new ModuloResource($modulo);
    }

    public function destroy(Modulo $modulo)
    {
        $modulo->delete();
        return response()->json(['message' => 'Módulo eliminado'], 200);
    }
}