<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatriculaHelper
{
    /**
     * Genera código de matrícula único.
     * Formato: AÑO + CORRELATIVO (5 dígitos)
     * Ejemplo: 202600001
     *
     * @param \App\Models\User $user
     * @return string
     */
    public static function generarCodigo(User $user): string
    {
        $anio = $user->created_at ? $user->created_at->year : now()->year;

        // Obtener el último correlativo usado para el mismo año
        $ultimoCodigo = User::where('matricula', 'LIKE', $anio . '%')
            ->orderBy('matricula', 'desc')
            ->value('matricula');

        if ($ultimoCodigo) {
            $correlativo = (int) substr($ultimoCodigo, -5) + 1;
        } else {
            $correlativo = 1;
        }

        // Asegurar 5 dígitos
        $correlativoStr = str_pad($correlativo, 5, '0', STR_PAD_LEFT);
        return $anio . $correlativoStr;
    }
}