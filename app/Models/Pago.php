<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'Pago';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'idCuota',
        'idUsuario',
        'monto',
        'metodo',
        'comprobante',
        'observacion',
        'registrado_por'
    ];

    public function cuota()
    {
        return $this->belongsTo(Cuota::class, 'idCuota');
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