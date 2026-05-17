<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'Pago';

    protected $fillable = [
        'idCuota',
        'idUsuario',
        'monto',
        'metodo',
        'comprobante',
        'observacion',
        'registrado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class, 'idCuota', 'idCuota');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}