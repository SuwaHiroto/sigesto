<?php

namespace App\Http\Requests\SolicitudService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_cliente' => 'required|exists:perfiles_clientes,id_cliente',
            'descripcion_problema' => 'required|string|min:10',
            'direccion_servicio' => 'required|string|max:255',
            'es_urgente' => 'nullable|boolean',

            // ✅ NUEVO: Materiales que el cliente ya tiene (opcional)
            'materiales_cliente' => 'nullable|string|max:1000',
            
            // ✅ NUEVO: Coordenadas GPS (opcionales)
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
        
            'fecha_preferida' => 'nullable|date|after_or_equal:today',
            'hora_preferida' => 'nullable|date_format:H:i',
            'notas_disponibilidad' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'id_cliente.exists' => 'El cliente seleccionado no existe en el sistema.',
            'descripcion_problema.min' => 'La descripción debe tener al menos 10 caracteres.',
            'fecha_preferida.after_or_equal' => 'La fecha preferida debe ser hoy o en el futuro.',
            'hora_preferida.date_format' => 'El formato de hora debe ser HH:MM (ej. 10:00).',
            'es_urgente.boolean' => 'El campo de urgencia debe ser verdadero o falso.',
            'materiales_cliente.max' => 'La descripción de materiales no puede exceder los 1000 caracteres.',
        ];
    }
}
