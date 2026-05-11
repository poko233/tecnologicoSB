<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rol' => 'required|string|max:40|unique:rol,rol',
            'descripcion' => 'nullable|string',
        ]);

        $nuevoRol = Rol::create([
            'rol' => $request->rol,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rol creado correctamente',
            'rol' => $nuevoRol
        ], 201);
    }

    public function show(string $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'rol' => $rol
        ]);
    }

    public function update(Request $request, string $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $request->validate([
            'rol' => 'required|string|max:40|unique:rol,rol,' . $id,
            'descripcion' => 'nullable|string',
        ]);

        $rol->update([
            'rol' => $request->rol,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado correctamente',
            'rol' => $rol
        ]);
    }

    public function destroy(string $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $rol->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente'
        ]);
    }
}