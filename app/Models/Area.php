<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $table = 'Area';
    protected $primaryKey = 'idArea';

    protected $fillable = [
        'nombre',
        'descripccion',
        'estado',
    ];

    public function carreras()
    {
        return $this->hasMany(Carrera::class, 'idArea', 'idArea');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo');
    }
}