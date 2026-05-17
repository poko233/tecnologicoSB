<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cuota extends Model
{
    protected $table = 'Cuota';
    protected $primaryKey = 'idCuota';

    protected $fillable = [
        'idUsuario',
        'idPlanPago',
        'monto',
        'numeroCuota',
        'tipo',
        'descuento',
        'fecha_vencimiento',
        'estadoCuota',
        'fecha_pago',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function planPago(): BelongsTo
    {
        return $this->belongsTo(PlanPago::class, 'idPlanPago');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class, 'idCuota', 'idCuota');
    }
}