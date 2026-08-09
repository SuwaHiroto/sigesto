<?php

namespace App\Http\Requests\UsuarioService;

use Illuminate\Foundation\Http\FormRequest;

class RegistroClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:6|confirmed',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'dni_ruc' => 'required|string|min:8|max:11', // ✅ Ahora es obligatorio
        ];
    }
}
