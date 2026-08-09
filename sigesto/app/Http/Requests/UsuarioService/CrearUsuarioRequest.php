<?php

namespace App\Http\Requests\UsuarioService;

use Illuminate\Foundation\Http\FormRequest;

class CrearUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    $rules = [
        'nombres' => 'required|string|max:100',
        'apellidos' => 'required|string|max:100',
        'email' => 'required|email|unique:usuarios,email',
        'password' => 'required|string|min:6',
        'id_rol' => 'required|exists:roles,id_rol',
        'telefono' => 'nullable|string|max:20',
        'direccion' => 'nullable|string|max:255',
    ];

    $idRol = $this->input('id_rol');

    if ($idRol == 2) { // TECNICO
        $rules['dni'] = 'required|string|min:8|max:8|unique:perfiles_tecnicos,dni';
        $rules['especialidad'] = 'nullable|string|max:100';
    } elseif ($idRol == 1) { // ADMINISTRADOR
        // Admin no requiere campos adicionales
    } elseif ($idRol == 3) { // CLIENTE
        $rules['telefono'] = 'nullable|string|max:20';
        $rules['direccion'] = 'nullable|string|max:255';
        $rules['dni_ruc'] = 'nullable|string|max:20'; // ✅ AGREGAR ESTA LÍNEA
    }

    return $rules;
}

// Agrega también los mensajes personalizados
public function messages(): array
{
    return [
        'dni.required' => 'El DNI es obligatorio para técnicos.',
        'dni.unique' => 'El DNI ya está registrado en otro técnico.',
        'dni.min' => 'El DNI debe tener 8 caracteres.',
        'dni.max' => 'El DNI debe tener 8 caracteres.',
        'dni_ruc.max' => 'El DNI/RUC no puede exceder los 20 caracteres.', // ✅ NUEVO
    ];
}
}
