<?php

namespace App\Http\Requests\FinanzasService;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarAdelantoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_pagado' => 'required|numeric|min:1',
            'metodo_pago' => 'required|string|in:TRANSFERENCIA,YAPE,PLIN',

            // ✅ FLEXIBLE: Al menos uno es obligatorio
            'nro_operacion' => 'nullable|string|max:50|required_without:comprobante,required_without:url_comprobante',
            'url_comprobante' => 'nullable|url|max:500',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // ✅ Nuevo: archivo
        ];
    }

    public function messages(): array
    {
        return [
            'monto_pagado.required' => 'El monto del adelanto es obligatorio.',
            'monto_pagado.min' => 'El monto mínimo es 1 sol.',
            'metodo_pago.required' => 'Debe indicar el método de pago.',
            'metodo_pago.in' => 'El método de pago debe ser TRANSFERENCIA, YAPE o PLIN.',
            'nro_operacion.required_without' => 'Debe ingresar el número de operación o subir el comprobante.',
            'url_comprobante.required_without' => 'Debe subir el comprobante o ingresar el número de operación.',
            'url_comprobante.url' => 'La URL del comprobante no tiene un formato válido.',
            'comprobante.file' => 'El comprobante debe ser un archivo válido.',
            'comprobante.max' => 'El comprobante no puede exceder 5MB.',
            'comprobante.mimes' => 'El comprobante debe ser una imagen (jpg, png) o PDF.',
        ];
    }
}
