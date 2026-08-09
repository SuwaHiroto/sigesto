<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCatalogo extends Model
{
    protected $table = 'items_catalogo';
    protected $primaryKey = 'id_item';

    protected $fillable = [
        'sku_codigo',
        'tipo_item',
        'nombre',
        'descripcion',
        'unidad_medida',
        'precio_ref',
        'activo'
    ];

    protected $casts = [
        'precio_ref' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function materialesRequeridos()
    {
        return $this->belongsToMany(
            ItemCatalogo::class,
            'servicio_materiales',
            'id_servicio',
            'id_material'
        )->withPivot('cantidad_sugerida')->withTimestamps();
    }

    public function usadoEnServicios()
    {
        return $this->belongsToMany(
            ItemCatalogo::class,
            'servicio_materiales',
            'id_material',
            'id_servicio'
        )->withPivot('cantidad_sugerida')->withTimestamps();
    }
}
