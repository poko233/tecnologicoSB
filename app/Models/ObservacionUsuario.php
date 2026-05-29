<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObservacionUsuario extends Model
{
    protected $table = 'observaciones_usuario';

    protected $fillable = [
        'user_id',
        'tipo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por', 'id');
    }

    /**
     * Scope: observaciones activas hoy (sin fecha_fin o fecha_fin >= hoy).
     */
    public function scopeActivas($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_fin')
              ->orWhere('fecha_fin', '>=', now()->toDateString());
        });
    }
}
