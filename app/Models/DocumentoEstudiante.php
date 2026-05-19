<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoEstudiante extends Model
{
    protected $table = 'DocumentoEstudiante';

    protected $primaryKey = 'idDocumentoEstudiante';

    public $timestamps = true;

    protected $fillable = [
        'nombreDocumento',
        'ubicacionArchivo',
        'estadoDocumento',
        'idUsuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'id');
    }
}