<?php

namespace App\Http\Requests\SolicitudService;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Items de la cotización
            'items' => 'nullable|array',
            'items.*.id_item' => 'required_with:items|integer|exists:items_catalogo,id_item',
            'items.*.cantidad' => 'required_with:items|numeric|min:0.01',
            'items.*.precio_aplicado' => 'required_with:items|numeric|min:0',

            // ✅ Coordinación de visita (extraído de CoordinarVisitaRequest)
            'fecha_coordinada' => 'nullable|date|after_or_equal:today',
            'hora_coordinada' => 'nullable|date_format:H:i',
            'notas_coordinacion' => 'nullable|string|max:500',

            // Otros datos generales
            'descripcion_problema' => 'nullable|string|min:10',
            'direccion_servicio' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.id_item.exists' => 'Uno de los materiales no existe en el catálogo.',
            'items.*.cantidad.min' => 'La cantidad mínima es 0.01.',
            'items.*.precio_aplicado.min' => 'El precio debe ser mayor o igual a 0.',
            'fecha_coordinada.after_or_equal' => 'La fecha coordinada debe ser hoy o en el futuro.',
            'hora_coordinada.date_format' => 'El formato de hora debe ser HH:MM (ej. 10:00).',
        ];
    }
}
