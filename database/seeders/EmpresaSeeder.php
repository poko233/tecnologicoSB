<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('empresa')->count() === 0) {
            DB::table('empresa')->insert([
                'EMPRESA'              => 'Nombre de tu empresa',
                'SLOGAN'               => '',
                'SIGLA'                => '',
                'TELEFONO'             => '',
                'CELULAR'              => '',
                'EMAIL'                => '',
                'DIRECCION'            => '',
                'RESPONSABLE'          => '',
                'LATITUD'              => '',
                'LONGITUD'             => '',
                'OBJETO'               => '',
                'MISION'               => '',
                'VISION'               => '',
                'FACEBOOK'             => '',
                'INSTAGRAM'            => '',
                'TIKTOK'               => '',
                'LINKEDIN'             => '',
                'CARRITO'              => 'INACTIVO',
                'TIPO_CAMBIO'          => 0,

                'LOGO_CUADRADO'        => '',
                'LOGO_LARGO'           => '',
                'BANER_INICIO'         => '',
                'ICONO'                => '',
                'TITULO_CIERRE'        => '',
                'MENSAJE_CIERRE'       => '',
                'TITULO_INICIO'        => '',
                'MENSAJE_INICIO'       => '',
                'DOMINIO'              => '',
                'SMTP_CORREO'          => '',
                'CORREO_INSTITUCIONAL' => '',
                'PWD_INSTITUCIONAL'    => '',
            ]);
        }
    }
}