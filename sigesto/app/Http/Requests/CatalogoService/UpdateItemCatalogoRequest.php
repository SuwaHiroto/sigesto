<?php

namespace App\Http\Requests\CatalogoService;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtenemos el ID de la URL para ignorarlo en la validación de 'unique'
        $id = $this->route('id');

        return [
            'sku_codigo' => "nullable|string|max:50|unique:items_catalogo,sku_codigo,{$id},id_item",
            'tipo_item' => 'sometimes|in:MATERIAL,SERVICIO,EQUIPO',
            'nombre' => 'sometimes|string|max:150',
            'descripcion' => 'nullable|string',
            'unidad_medida' => 'nullable|string|max:20',
            'precio_ref' => 'sometimes|numeric|min:0',
            'activo' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sku_codigo.unique' => 'El código SKU ya está registrado en otro item del catálogo.',
            'precio_ref.min' => 'El precio de referencia no puede ser negativo.',
        ];
    }
}
