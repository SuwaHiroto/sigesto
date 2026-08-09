<?php

namespace App\Http\Requests\CatalogoService;

use Illuminate\Foundation\Http\FormRequest;

class VincularMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_servicio' => 'required|exists:items_catalogo,id_item',
            'id_material' => 'required|exists:items_catalogo,id_item|different:id_servicio',
            'cantidad_sugerida' => 'nullable|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'id_material.different' => 'No puedes vincular un item consigo mismo.',
            'cantidad_sugerida.min' => 'La cantidad sugerida debe ser mayor a 0.',
        ];
    }
}
