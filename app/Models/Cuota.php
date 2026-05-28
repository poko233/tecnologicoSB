<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $table = 'Cuota';
    protected $primaryKey = 'idCuota';
    public $timestamps = true;

    protected $fillable = [
        'idUsuario',
        'idCarrera',
        'tipo',
        'monto',
        'numeroCuota',
        'fecha_vencimiento',
        'descuento',
        'estadoCuota',
        'fecha_pago',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'idCarrera', 'idCarrera');
    }
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function pagos()
    {
        return $this->belongsToMany(Pago::class, 'pago_cuota', 'idCuota', 'idPago')
                    ->withPivot('monto_pagado')
                    ->withTimestamps();
    }

    public function pago()
    {
        return $this->belongsToMany(Pago::class, 'pago_cuota', 'idCuota', 'idPago')
                    ->latest()
                    ->limit(1);
    }
}