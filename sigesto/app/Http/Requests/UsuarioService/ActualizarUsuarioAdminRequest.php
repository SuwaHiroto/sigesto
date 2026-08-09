<?php

namespace App\Http\Requests\UsuarioService;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarUsuarioAdminRequest extends FormRequest
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
            'password' => 'sometimes|string|min:6',
            'id_rol' => 'sometimes|exists:roles,id_rol',
        ];
    }
}
