<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Empresa extends Model
{
    protected $table = 'empresa';
 
    protected $primaryKey = 'ID_EMPRESA';
 
    public $timestamps = false;
 
    protected $fillable = [
        'EMPRESA',
        'SLOGAN',
        'SIGLA',
        'TELEFONO',
        'CELULAR',
        'EMAIL',
        'DIRECCION',
        'RESPONSABLE',
        'LATITUD',
        'LONGITUD',
        'OBJETO',
        'MISION',
        'VISION',
        'FACEBOOK',
        'INSTAGRAM',
        'TIKTOK',
        'LINKEDIN',
        'CARRITO',
        'TIPO_CAMBIO',
        'LOGO_CUADRADO',
        'LOGO_LARGO',
        'BANER_INICIO',
        'ICONO',
        'TITULO_CIERRE',
        'MENSAJE_CIERRE',
        'TITULO_INICIO',
        'MENSAJE_INICIO',
        'DOMINIO',
        'SMTP_CORREO',
        'CORREO_INSTITUCIONAL',
        'PWD_INSTITUCIONAL',
    ];
 
    /**
     * Campos sensibles excluidos de la serialización JSON.
     * Nunca expongas contraseñas en respuestas de API.
     */
    protected $hidden = [
        'PWD_INSTITUCIONAL',
    ];
 
    protected $casts = [
        'TIPO_CAMBIO' => 'float',
    ];
}