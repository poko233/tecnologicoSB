<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formulario extends Model
{
    protected $table = 'formulario';

    protected $fillable = [
        'formulario',
        'descripcion',
        'ruta',
        'componente',
    ];

    

    public function modulos()
    {
        return $this->belongsToMany(Modulo::class, 'formulario_modulo', 'id_formulario', 'id_modulo');
    }

    /**
     * Permisos CRUD definidos para este formulario.
     * Úsalo para consultar: $formulario->permisos()->where('id_rol', $rolId)->first()
     */
    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'id_formulario');
    }
}