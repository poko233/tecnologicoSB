<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPago extends Model
{
    protected $table = 'PlanPago';

    protected $fillable = [
        'idUsuario',
        'gestion',
        'matricula_economica',
        'numero_cuotas',
        'monto_cuota_promocion',
        'monto_cuota_normal',
        'matricula_numero',
        'estado',
    ];

    protected $casts = [
        'gestion' => 'integer',
        'matricula_economica' => 'decimal:2',
        'monto_cuota_promocion' => 'decimal:2',
        'monto_cuota_normal' => 'decimal:2',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class, 'idPlanPago');
    }
}