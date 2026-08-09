<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTrabajo extends Model
{
    use HasFactory;

    protected $table = 'tipos_trabajo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con los ítems sugeridos del catálogo
     */
    public function itemsSugeridos()
    {
        return $this->belongsToMany(
            ItemCatalogo::class,
            'tipo_trabajo_items_sugeridos',
            'id_tipo_trabajo',
            'id_item'
        )->withPivot('cantidad_sugerida', 'unidad_medida', 'obligatorio');
    }
}
