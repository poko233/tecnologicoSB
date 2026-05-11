<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FormularioResource;
use App\Models\Formulario;
use App\Models\Modulo;
use Illuminate\Http\Request;

class FormularioModuloController extends Controller
{
    public function index(Modulo $modulo)
    {
        return FormularioResource::collection(
            $modulo->formularios
        );
    }

    public function store(Request $request, Modulo $modulo)
    {
        $request->validate([
            'formulario'  => 'required|string|max:40',
            'descripcion' => 'nullable|string',
            'ruta'        => 'nullable|string|max:40',
        ]);

        $formulario = Formulario::create($request->only('formulario', 'descripcion', 'ruta'));

        $modulo->formularios()->attach($formulario->id);

        return (new FormularioResource($formulario))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Modulo $modulo, Formulario $formulario)
    {
        $request->validate([
            'formulario'  => 'required|string|max:40',
            'descripcion' => 'nullable|string',
            'ruta'        => 'nullable|string|max:40',
        ]);

        $formulario->update($request->only('formulario', 'descripcion', 'ruta'));

        return new FormularioResource($formulario);
    }

    public function destroy(Modulo $modulo, Formulario $formulario)
    {
        $modulo->formularios()->detach($formulario->id);


        return response()->json(['message' => 'Formulario desvinculado del módulo'], 200);
    }

    public function sync(Request $request, Modulo $modulo)
    {
        $request->validate([
            'formulario_ids'   => 'required|array',
            'formulario_ids.*' => 'exists:formulario,id',
        ]);

        $modulo->formularios()->sync($request->formulario_ids);

        return response()->json([
            'message'    => 'Formularios sincronizados',
            'formularios' => $modulo->formularios,
        ]);
    }
}