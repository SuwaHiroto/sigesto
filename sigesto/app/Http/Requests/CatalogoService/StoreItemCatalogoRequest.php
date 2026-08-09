<?php

namespace App\Http\Requests\CatalogoService;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo usuarios autenticados pueden crear items (se valida en la ruta con middleware)
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_codigo' => 'nullable|string|max:50|unique:items_catalogo,sku_codigo',
            'tipo_item' => 'required|in:MATERIAL,SERVICIO,EQUIPO',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'unidad_medida' => 'nullable|string|max:20',
            'precio_ref' => 'required|numeric|min:0',
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_item.in' => 'El tipo de item debe ser MATERIAL, SERVICIO o EQUIPO.',
            'precio_ref.min' => 'El precio de referencia no puede ser negativo.',
            'sku_codigo.unique' => 'El código SKU ya está registrado en el catálogo.',
        ];
    }
}
