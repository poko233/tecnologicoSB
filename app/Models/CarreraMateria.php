<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarreraMateria extends Model
{
    protected $table = 'CarreraMateria';
    protected $primaryKey = 'idCarreraMateria';

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';

    protected $fillable = [
        'idMateria',
        'idCarrera',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'idCarrera', 'idCarrera');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'idMateria', 'idMateria');
    }
}