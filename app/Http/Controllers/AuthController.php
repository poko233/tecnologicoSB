<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
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

        // 1. Buscar usuario
        $user = User::where('usuario', $login)
            ->orWhere('ci', $login)
            ->orWhere('email', $login)
            ->first();

        // 2. Validar existencia y contraseña
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        // 3. Validar estado
        if ($user->estado === 'INACTIVO') {
            return response()->json([
                'message' => 'Su cuenta se encuentra inactiva. Contacte con el administrador.',
            ], 403);
        }

        // 4. Crear token
        $token = $user->createToken('mobile')->plainTextToken;

        // 5. Cargar roles y formatear para el frontend
        $user->load('roles');
        $userData = $user->toArray();
        $userData['roles'] = $user->roles->pluck('rol')->toArray();

        return response()->json([
            'token' => $token,
            'user' => $userData,
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
                'fecha_nac' => 'required|date|before:today|after:' . now()->subYears(150)->format('Y-m-d'),
                'email' => 'nullable|email|max:80',
                'telefono' => 'nullable|string|max:10',
                'celular' => 'nullable|string|max:10',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:rol,rol',   // <-- validar que exista el nombre de rol
            ], [
                'roles.required' => 'Debe seleccionar al menos un rol.',
                'roles.*.exists' => 'El rol seleccionado no es válido.',
            ]);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json([
                'message' => $firstError ?? 'Algunos datos ya se encuentran registrados o son inválidos.',
                'errors' => $e->errors(),
            ], 422);
        }

        // Verificar permisos del usuario autenticado para asignar los roles solicitados
        $currentUser = $request->user();
        $currentRoles = $currentUser->roles->pluck('rol')->toArray();
        $allowedRolesForAssign = $this->allowedRolesForAssign($currentRoles);

        $requestedRoleNames = $validated['roles'];   // array de strings (nombres de rol)

        foreach ($requestedRoleNames as $requestedRole) {
            if (!in_array($requestedRole, $allowedRolesForAssign)) {
                return response()->json([
                    'message' => "No tiene permiso para asignar el rol '{$requestedRole}'.",
                ], 403);
            }
        }

        // Obtener los IDs correspondientes a los nombres de rol
        $requestedRoleIds = \App\Models\Rol::whereIn('rol', $requestedRoleNames)
            ->pluck('id')
            ->toArray();

        // Crear usuario
        $user = \App\Models\User::create([
            'usuario' => $validated['usuario'],
            'password' => $validated['password'],
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

        // Asignar roles
        $user->roles()->sync($requestedRoleIds);
        $user->load('roles');
        $userData = $user->toArray();
        $userData['roles'] = $user->roles->pluck('rol')->toArray();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $userData,
            'message' => 'Usuario registrado exitosamente',
        ], 201);
    }

    // Jerarquía de asignación de roles
    private function allowedRolesForAssign(array $currentUserRoles): array
    {
        $allowed = [];

        foreach ($currentUserRoles as $role) {
            switch ($role) {
                case 'Administrador':
                    $allowed = array_merge($allowed, ['Personal']);
                    break;
                case 'Rector':
                    $allowed = array_merge($allowed, ['Administrador', 'Personal']);
                    break;
                case 'Director Academico':
                    $allowed = array_merge($allowed, [
                        'Rector',
                        'Administrador',
                        'Personal',
                        'Director Administrativo',
                    ]);
                    break;
                case 'Director Administrativo':
                    $allowed = array_merge($allowed, ['Rector', 'Administrador', 'Personal']);
                    break;

                case 'Fundador':
                    $allowed = array_merge($allowed, [
                        'Rector',
                        'Administrador',
                        'Personal',
                        'Director Academico',
                        'Director Administrativo',
                    ]);
                    break;
            }
        }

        return array_unique($allowed);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');
        $userData = $user->toArray();
        $userData['roles'] = $user->roles->pluck('rol')->toArray();
        return response()->json($userData);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }
    /**
     * PUT /api/change-password
     *
     * Cambia la contraseña del usuario autenticado.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Verificar que la contraseña actual sea correcta
        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.',
            ], 422);
        }

        // (Opcional pero recomendable) No permitir que la nueva contraseña sea igual a la anterior
        if (Hash::check($data['new_password'], $user->password)) {
            return response()->json([
                'message' => 'La nueva contraseña no puede ser igual a la anterior.',
            ], 422);
        }

        // Actualizar la contraseña
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ], 200);
    }
}