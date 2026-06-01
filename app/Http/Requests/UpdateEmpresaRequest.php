<?php
 
namespace App\Http\Requests;
 
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
 
class UpdateEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajusta con tu lógica de autenticación (sanctum)
        return true;
    }
 
    public function rules(): array
    {
        return [
            'EMPRESA'               => ['sometimes', 'string', 'max:80'],
            'SLOGAN'                => ['sometimes', 'nullable', 'string'],
            'SIGLA'                 => ['sometimes', 'nullable', 'string', 'max:200'],
            'TELEFONO'              => ['sometimes', 'nullable', 'string', 'max:11'],
            'CELULAR'               => ['sometimes', 'nullable', 'string', 'max:11'],
            'EMAIL'                 => ['sometimes', 'nullable', 'email', 'max:80'],
            'DIRECCION'             => ['sometimes', 'nullable', 'string'],
            'RESPONSABLE'           => ['sometimes', 'nullable', 'string', 'max:80'],
            'LATITUD'               => ['sometimes', 'nullable', 'string', 'max:80'],
            'LONGITUD'              => ['sometimes', 'nullable', 'string', 'max:80'],
 
            'OBJETO'                => ['sometimes', 'nullable', 'string'],
            'MISION'                => ['sometimes', 'nullable', 'string'],
            'VISION'                => ['sometimes', 'nullable', 'string'],
 
            'FACEBOOK'              => ['sometimes', 'nullable', 'string', 'max:40'],
            'INSTAGRAM'             => ['sometimes', 'nullable', 'string', 'max:40'],
            'TIKTOK'                => ['sometimes', 'nullable', 'string', 'max:40'],
            'LINKEDIN'              => ['sometimes', 'nullable', 'string', 'max:40'],
 
            'CARRITO'               => ['sometimes', 'in:ACTIVO,INACTIVO'],
            'TIPO_CAMBIO'           => ['sometimes', 'nullable', 'numeric', 'min:0'],
 
            'LOGO_CUADRADO'         => ['sometimes', 'nullable', 'string', 'max:80'],
            'LOGO_LARGO'            => ['sometimes', 'nullable', 'string', 'max:80'],
            'BANER_INICIO'          => ['sometimes', 'nullable', 'string', 'max:80'],
            'ICONO'                 => ['sometimes', 'nullable', 'string', 'max:40'],
 
            'TITULO_CIERRE'         => ['sometimes', 'nullable', 'string', 'max:80'],
            'MENSAJE_CIERRE'        => ['sometimes', 'nullable', 'string'],
            'TITULO_INICIO'         => ['sometimes', 'nullable', 'string', 'max:80'],
            'MENSAJE_INICIO'        => ['sometimes', 'nullable', 'string'],
 
            'DOMINIO'               => ['sometimes', 'nullable', 'string', 'max:200'],
            'SMTP_CORREO'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'CORREO_INSTITUCIONAL'  => ['sometimes', 'nullable', 'email', 'max:80'],
            'PWD_INSTITUCIONAL'     => ['sometimes', 'nullable', 'string', 'max:80'],
        ];
    }
 
    public function messages(): array
    {
        return [
            'EMAIL.email'                   => 'El email de empresa no es válido.',
            'CORREO_INSTITUCIONAL.email'    => 'El correo institucional no es válido.',
            'CARRITO.in'                    => 'El valor de carrito debe ser ACTIVO o INACTIVO.',
            'TIPO_CAMBIO.numeric'           => 'El tipo de cambio debe ser un número.',
            'TIPO_CAMBIO.min'               => 'El tipo de cambio no puede ser negativo.',
        ];
    }
 

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}