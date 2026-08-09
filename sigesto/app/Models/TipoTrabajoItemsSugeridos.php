<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoTrabajoItemsSugeridos extends Model
{
    protected $table = 'tipo_trabajo_items_sugeridos';

    protected $fillable = [
        'id_tipo_trabajo',
        'id_item',
        'cantidad_sugerida',
    ];

    public $timestamps = false; // Si tu tabla no tiene created_at/updated_at

    /**
     * Relación con TipoTrabajo
     */
    public function tipoTrabajo(): BelongsTo
    {
        return $this->belongsTo(TipoTrabajo::class, 'id_tipo_trabajo');
    }

    /**
     * Relación con ItemCatalogo
     */
    public function itemCatalogo(): BelongsTo
    {
        return $this->belongsTo(ItemCatalogo::class, 'id_item');
    }
}