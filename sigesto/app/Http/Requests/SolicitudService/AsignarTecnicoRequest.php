<?php

namespace App\Http\Requests\SolicitudService;

use Illuminate\Foundation\Http\FormRequest;

class AsignarTecnicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tecnico' => 'required|exists:perfiles_tecnicos,id_tecnico',
            // ✅ Campos de coordinación opcionales
            'fecha_coordinada' => 'nullable|date|after_or_equal:today',
            'hora_coordinada' => 'nullable|date_format:H:i',
            'notas_coordinacion' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'id_tecnico.exists' => 'El técnico seleccionado no existe.',
            'fecha_coordinada.after_or_equal' => 'La fecha coordinada debe ser hoy o en el futuro.',
            'hora_coordinada.date_format' => 'El formato de hora debe ser HH:MM (ej. 10:00).',
        ];
    }
}
