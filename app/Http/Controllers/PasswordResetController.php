<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function sendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
        ], [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingrese un correo válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $correo = strtolower(trim($request->correo));

        $user = User::where('email', $correo)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No existe una cuenta registrada con ese correo.',
            ], 404);
        }

        $code = (string) random_int(100000, 999999);

        PasswordResetCode::where('correo', $correo)
            ->where('used', false)
            ->update(['used' => true]);

        PasswordResetCode::create([
            'correo' => $correo,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        Mail::raw("Tu código de recuperación es: {$code}\n\nEste código vence en 10 minutos.", function ($message) use ($correo) {
            $message->to($correo)
                ->subject('Código de recuperación de contraseña');
        });

        return response()->json([
            'message' => 'Código enviado correctamente a tu correo.',
        ], 200);
    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'code' => 'required|string|size:6',
        ], [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingrese un correo válido.',
            'code.required' => 'El código es obligatorio.',
            'code.size' => 'El código debe tener 6 dígitos.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $correo = strtolower(trim($request->correo));
        $code = trim($request->code);

        $resetCode = PasswordResetCode::where('correo', $correo)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$resetCode) {
            return response()->json([
                'message' => 'Código inválido o expirado.',
            ], 422);
        }

        return response()->json([
            'message' => 'Código verificado correctamente.',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'Ingrese un correo válido.',
            'code.required' => 'El código es obligatorio.',
            'code.size' => 'El código debe tener 6 dígitos.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $correo = strtolower(trim($request->correo));
        $code = trim($request->code);

        $resetCode = PasswordResetCode::where('correo', $correo)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$resetCode) {
            return response()->json([
                'message' => 'Código inválido o expirado.',
            ], 422);
        }

        $user = User::where('email', $correo)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $resetCode->update([
            'used' => true,
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ], 200);
    }
}