<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla: permiso
 * Fuente de verdad de los permisos CRUD por Rol+Formulario.
 *
 * Lógica del sidebar:
 *   Un módulo aparece si el rol del usuario tiene ver=true
 *   en al menos un formulario de ese módulo.
 */
class Permiso extends Model
{
    protected $table = 'permiso';

    protected $fillable = [
        'id_rol',
        'id_formulario',
        'ver',
        'crear',
        'editar',
        'eliminar',
    ];

    protected $casts = [
        'ver'      => 'boolean',
        'crear'    => 'boolean',
        'editar'   => 'boolean',
        'eliminar' => 'boolean',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function formulario()
    {
        return $this->belongsTo(Formulario::class, 'id_formulario');
    }
}
