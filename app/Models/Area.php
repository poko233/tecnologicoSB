<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'Area';
    protected $primaryKey = 'idArea';

    protected $fillable = [
        'nombre',
        'descripccion',
    ];

    public function carreras()
    {
        return $this->hasMany(Carrera::class, 'idArea', 'idArea');
    }
}