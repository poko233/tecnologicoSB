<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario' => 'required|string',
                'password' => 'required|string',
            ], [
                'usuario.required' => 'El campo usuario es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
            ]);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json([
                'message' => $firstError ?? 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $login = trim($validated['usuario']);

        // Permitimos login por usuario, ci o email por mayor flexibilidad
        $user = User::where('usuario', $login)
            ->orWhere('ci', $login)
            ->orWhere('email', $login)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            // El front-end mostrará este message en el Toast
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        if ($user->estado === 'INACTIVO') {
            return response()->json([
                'message' => 'Su cuenta se encuentra inactiva. Contacte con el administrador.',
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'message' => 'Sesión iniciada correctamente',
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuario' => 'required|string|max:40|unique:user,usuario',
                'password' => 'required|string|min:6',
                'ci' => 'required|string|max:12|unique:user,ci',
                'nombres' => 'required|string|max:40',
                'apellidos' => 'required|string|max:40',
                'genero' => 'required|in:MASCULINO,FEMENINO',
                'fecha_nac' => 'required|date',
                'email' => 'nullable|email|max:80',
                'telefono' => 'nullable|string|max:10',
                'celular' => 'nullable|string|max:10',
            ], [
                'usuario.required' => 'El nombre de usuario es obligatorio.',
                'usuario.unique' => 'Este nombre de usuario ya está en uso.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'ci.required' => 'El CI es obligatorio.',
                'ci.unique' => 'Este número de CI ya se encuentra registrado.',
                'nombres.required' => 'Los nombres son obligatorios.',
                'apellidos.required' => 'Los apellidos son obligatorios.',
                'genero.required' => 'El género es obligatorio.',
                'genero.in' => 'El género debe ser MASCULINO o FEMENINO.',
                'fecha_nac.required' => 'La fecha de nacimiento es obligatoria.',
                'fecha_nac.date' => 'La fecha de nacimiento no es válida.',
                'email.email' => 'El correo electrónico no es válido.',
            ]);
        } catch (ValidationException $e) {
            // Extraemos el primer error específico para mandarlo en "message"
            // Así tu httpClient.ts lo atrapa de json.message y lo muestra en el Toast
            $firstError = collect($e->errors())->flatten()->first();
            
            return response()->json([
                'message' => $firstError ?? 'Algunos datos ya se encuentran registrados o son inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'usuario' => $validated['usuario'],
            'password' => $validated['password'], // Se hasheará automáticamente por el cast 'hashed' en el modelo User
            'ci' => $validated['ci'],
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'genero' => $validated['genero'],
            'fecha_nac' => $validated['fecha_nac'],
            'email' => isset($validated['email']) ? strtolower(trim($validated['email'])) : null,
            'telefono' => $validated['telefono'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'estado' => 'ACTIVO',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'message' => 'Usuario registrado exitosamente',
        ], 201);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        // Revocamos el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }
}
