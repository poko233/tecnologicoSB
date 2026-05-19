<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarreraUsuario extends Model
{
    protected $table = 'CarreraUsuario';
    protected $primaryKey = 'idCarreraUsuario';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

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