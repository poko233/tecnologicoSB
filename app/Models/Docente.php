<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    protected $table = 'Docente';
    protected $primaryKey = 'idDocente';

    protected $fillable = [
        'idDocente',
        'profesion',
        'abreviaturaProfesional',
        'fechaRegistro',
        'estadoDocente',
    ];

    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idDocente', 'id');
    }
}