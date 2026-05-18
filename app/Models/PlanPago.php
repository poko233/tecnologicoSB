<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPago extends Model
{
    protected $table = 'PlanPago';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'idUsuario',
        'gestion',
        'matricula_economica',
        'numero_cuotas',
        'monto_cuota_promocion',
        'monto_cuota_normal',
        'matricula_numero',
        'estado'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'idPlanPago');
    }
}