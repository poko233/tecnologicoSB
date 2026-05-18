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
        'apellidoPaterno',   // ← cambio
        'apellidoMaterno',   // ← cambio
        'genero',
        'fecha_nac',
        'email',
        'telefono',
        'celular',
        'direccion',         // ← nuevo
        'matricula',         // ← nuevo
        'expedido',          // ← nuevo
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

    // Relación muchos a muchos con roles (tabla user_rol)
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'user_rol', 'id_user', 'id_rol');
    }

    // Relación muchos a muchos con sucursales (user_sucursal)
    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'user_sucursal', 'id_user', 'id_sucursal');
    }

    // ========== NUEVAS RELACIONES (para el módulo de cuotas) ==========

    public function planesPago()
    {
        return $this->hasMany(PlanPago::class, 'idUsuario');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'idUsuario');
    }

    // Método auxiliar para verificar roles (útil en permisos)
    public function hasRole(string $rolNombre): bool
    {
        return $this->roles()->where('rol', $rolNombre)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('rol', $roles)->exists();
    }
}