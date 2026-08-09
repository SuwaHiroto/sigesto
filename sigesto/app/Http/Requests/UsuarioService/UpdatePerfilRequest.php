<?php

namespace App\Http\Requests\UsuarioService;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => 'sometimes|string|max:100',
            'apellidos' => 'sometimes|string|max:100',
            'telefono' => 'sometimes|string|max:20',
            'direccion' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6|confirmed',
        ];
    }
}
