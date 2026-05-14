<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'modulo';

    protected $fillable = [
        'modulo',
        'descripcion',
        'icono',
    ];

    /** Roles que tienen acceso directo a este módulo (modulo_rol). */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'modulo_rol', 'id_modulo', 'id_rol');
    }

    /** Formularios hijos de este módulo, ordenados. */
    public function formularios()
    {
        return $this->belongsToMany(
            Formulario::class,
            'formulario_modulo',
            'id_modulo',
            'id_formulario'
        )->orderBy('id');
    }
}