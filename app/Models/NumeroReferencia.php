<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumeroReferencia extends Model
{
    protected $table = 'NumeroReferencia';

    protected $primaryKey = 'idNumeroReferencia';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'parentesco',
        'numeroReferencia',
        'nombreContactoReferencia',
        'idUsuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'id');
    }
}