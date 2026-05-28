<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Index permissions placeholder']);
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'Store permission placeholder']);
    }

    public function update(Request $request, $permiso)
    {
        return response()->json(['message' => 'Update permission placeholder']);
    }

    public function destroy($permiso)
    {
        return response()->json(['message' => 'Destroy permission placeholder']);
    }

    public function sync(Request $request)
    {
        return response()->json(['message' => 'Sync permissions placeholder']);
    }
}
