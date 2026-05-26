<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'Pago';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'idUsuario',
        'monto',
        'metodo',
        'comprobante',
        'observacion',
        'registrado_por'
    ];

    public function cuotas()
    {
        return $this->belongsToMany(Cuota::class, 'pago_cuota', 'idPago', 'idCuota')
                    ->withPivot('monto_pagado')
                    ->withTimestamps();
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}