<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $table = 'Cuota';
    protected $primaryKey = 'idCuota';
    public $timestamps = true;

    protected $fillable = [
        'idPlanPago',   // puede ser null
        'idUsuario',
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

    public function planPago()
    {
        return $this->belongsTo(PlanPago::class, 'idPlanPago');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function pago()
    {
        return $this->hasOne(Pago::class, 'idCuota');
    }
}