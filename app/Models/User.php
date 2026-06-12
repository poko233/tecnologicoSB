<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'usuario',
        'password',
        'ci',
        'nombres',
        'apellidoPaterno',
        'apellidoMaterno',
        'genero',
        'fecha_nac',
        'email',
        'telefono',
        'celular',
        'direccion',
        'matricula',
        'expedido',
        'codigo_qr',
        'verificacion',
        'foto',
        'estado',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'user_rol',
            'id_user',
            'id_rol'
        );
    }

    public function sucursales()
    {
        return $this->belongsToMany(
            Sucursal::class,
            'user_sucursal',
            'id_user',
            'id_sucursal'
        );
    }

    public function cuotas()
    {
        return $this->hasMany(
            Cuota::class,
            'idUsuario',
            'id'
        );
    }

    public function carreras()
    {
        return $this->belongsToMany(
            Carrera::class,
            'CarreraUsuario',
            'idUsuario',
            'idCarrera'
        )->withPivot('idCarreraUsuario');
    }

    public function numeroReferencias()
    {
        return $this->hasOne(
            NumeroReferencia::class,
            'idUsuario',
            'id'
        );
    }

    public function documentos()
    {
        return $this->hasMany(
            DocumentoEstudiante::class,
            'idUsuario',
            'id'
        );
    }

    public function hasRole(string $rolNombre): bool
    {
        return $this->roles()->where('rol', $rolNombre)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('rol', $roles)->exists();
    }
}