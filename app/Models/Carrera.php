<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'Carrera';

    protected $primaryKey = 'idCarrera';

    public $timestamps = true;

    const CREATED_AT = 'create_at';
    const UPDATED_AT = 'update_at';

    protected $fillable = [
        'nombreCarrera',
        'codigo',
        'duracionMeses',
        'cargaHoraria',
        'costo',
        'regimen',
        'denominacionTituloProfesional',
        'cuotaMes',
        'numeroCuotas',
        'estadoCarrera',
        'idArea',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'cuotaMes' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // Área a la que pertenece la carrera
    public function area()
    {
        return $this->belongsTo(Area::class, 'idArea', 'idArea');
    }

    // Relación con CarreraMateria
    public function carreraMaterias()
    {
        return $this->hasMany(
            CarreraMateria::class,
            'idCarrera',
            'idCarrera'
        );
    }

    // Materias de la carrera
    public function materias()
    {
        return $this->belongsToMany(
            Materia::class,
            'CarreraMateria',
            'idCarrera',
            'idMateria'
        );
    }

    // Usuarios inscritos
    public function usuarios()
    {
        return $this->belongsToMany(
            User::class,
            'CarreraUsuario',
            'idCarrera',
            'idUsuario'
        );
    }

    // Relación directa CarreraUsuario
    public function carreraUsuarios()
    {
        return $this->hasMany(
            CarreraUsuario::class,
            'idCarrera',
            'idCarrera'
        );
    }
}