<?php

namespace App\Http\Requests\SolicitudService;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ✅ CORREGIDO: Incluye TODOS los estados válidos
            'estado' => 'required|string|in:ASIGNADA,COTIZADA,REVISION_PAGO,APROBADA,RECHAZADA,EN_PROCESO,FINALIZADA,PAGADA,CANCELADA',
            'motivo_rechazo' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser uno de los valores permitidos.',
        ];
    }
}
