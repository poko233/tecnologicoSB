<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarreraUsuario extends Model
{
    protected $table = 'CarreraUsuario';
    protected $primaryKey = 'idCarreraUsuario';

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';

    protected $fillable = [
        'idCarrera',
        'idUsuario',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'idCarrera', 'idCarrera');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'id');
    }
}