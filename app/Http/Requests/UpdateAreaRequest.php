<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idArea = $this->route('area');
        return [
            'nombre'       => 'required|string|max:50|unique:Area,nombre,' . $idArea . ',idArea',
            'descripccion' => 'nullable|string',
            'estado'       => 'nullable|in:activo,inactivo',
        ];
    }
}