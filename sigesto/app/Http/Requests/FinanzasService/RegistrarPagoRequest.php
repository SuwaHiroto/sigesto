<?php

namespace App\Http\Requests\FinanzasService;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid_solicitud' => 'required|string|max:36',
            'monto_pagado' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|in:EFECTIVO,YAPE,PLIN,TRANSFERENCIA',

            // ✅ FLEXIBLE: Al menos uno es obligatorio
            'nro_operacion' => 'nullable|string|max:100|required_without:comprobante,required_without:url_comprobante',
            'url_comprobante' => 'nullable|url|max:500',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // ✅ Nuevo
        ];
    }

    public function messages(): array
    {
        return [
            'uuid_solicitud.required' => 'El UUID de la solicitud es obligatorio.',
            'uuid_solicitud.max' => 'El UUID no puede tener más de 36 caracteres.',
            'monto_pagado.required' => 'El monto a pagar es obligatorio.',
            'monto_pagado.min' => 'El monto a pagar debe ser mayor a 0.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
            'url_comprobante.url' => 'La URL del comprobante no tiene un formato válido.',
            'nro_operacion.required_without' => 'Debe ingresar el número de operación o subir el comprobante.',
            'url_comprobante.required_without' => 'Debe subir el comprobante o ingresar el número de operación.',
            'comprobante.file' => 'El comprobante debe ser un archivo válido.',
            'comprobante.max' => 'El comprobante no puede exceder 5MB.',
            'comprobante.mimes' => 'El comprobante debe ser una imagen (jpg, png) o PDF.',
        ];
    }
}
