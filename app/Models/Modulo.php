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

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'modulo_rol', 'id_modulo', 'id_rol');
    }

    public function formularios()
    {
        return $this->belongsToMany(Formulario::class, 'formulario_modulo', 'id_modulo', 'id_formulario');
    }
}