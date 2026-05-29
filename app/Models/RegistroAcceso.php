<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAcceso extends Model
{
    protected $table = 'registro_accesos';

    protected $fillable = [
        'user_id',
        'tipo_persona',
        'estado_mostrado',
        'color_alerta',
        'punto_control',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
