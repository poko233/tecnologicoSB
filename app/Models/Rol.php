<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';

    protected $fillable = [
        'rol',
        'descripcion',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'user_rol', 'id_rol', 'id_user');
    }

    public function modulos()
    {
        return $this->belongsToMany(Modulo::class, 'modulo_rol', 'id_rol', 'id_modulo');
    }

    /**
     * Permisos CRUD de este rol sobre cada formulario.
     * Tabla: permiso (id_rol, id_formulario, ver, crear, editar, eliminar)
     */
    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'id_rol');
    }

    /** Formularios a los que este rol tiene al menos 'ver=true'. */
    public function formulariosPermitidos()
    {
        return $this->belongsToMany(
            Formulario::class,
            'permiso',
            'id_rol',
            'id_formulario'
        )->wherePivot('ver', true)->withPivot(['crear', 'editar', 'eliminar']);
    }
}