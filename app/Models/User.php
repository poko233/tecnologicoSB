<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'usuario',
        'password',
        'ci',
        'nombres',
        'apellidos',
        'genero',
        'fecha_nac',
        'email',
        'telefono',
        'celular',
        'codigo_qr',
        'verificacion',
        'foto',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /** Roles del usuario (tabla pivote user_rol). */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'user_rol', 'id_user', 'id_rol');
    }

    /** Sucursales asignadas al usuario. */
    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'user_sucursal', 'id_user', 'id_sucursal');
    }
}
