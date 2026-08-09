<?php

namespace App\Http\Requests\EvidenciaService;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->has('archivos')) {
            return [
                'uuid_solicitud' => 'required|exists:solicitudes,uuid_solicitud',
                'archivos' => 'required|array|min:1|max:20',
                'archivos.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'tipos_evidencia' => 'required|array|min:1',
                'tipos_evidencia.*' => 'required|in:FOTO_ANTES,FOTO_DESPUES,COMPROBANTE_PAGO',
                'observaciones' => 'nullable|string|max:1000',
            ];
        }

        return [
            'uuid_solicitud' => 'required|exists:solicitudes,uuid_solicitud',
            'tipo_evidencia' => 'required|in:FOTO_ANTES,FOTO_DESPUES',
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observaciones' => 'nullable|string|max:1000', // ✅ AGREGAR
        ];
    }


    public function messages(): array
    {
        return [
            'uuid_solicitud.exists' => 'La solicitud no existe.',
            'tipo_evidencia.in' => 'Tipo de evidencia inválido.',
            'archivo.required' => 'Debe adjuntar un archivo.',
            'archivo.mimes' => 'El archivo debe ser imagen (jpg, jpeg, png) o PDF.',
            'archivo.max' => 'El archivo no puede pesar más de 5MB.',
            'archivos.max' => 'No puede subir más de 20 archivos a la vez.',
            'tipos_evidencia.size' => 'La cantidad de tipos debe coincidir con la cantidad de archivos.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 1000 caracteres.',
        ];
    }

    // ✅ Validación personalizada del tamaño
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('archivos') && $this->has('tipos_evidencia')) {
                if (count($this->archivos) !== count($this->tipos_evidencia)) {
                    $validator->errors()->add(
                        'tipos_evidencia',
                        'La cantidad de tipos debe coincidir con la cantidad de archivos.'
                    );
                }
            }
        });
    }
}
