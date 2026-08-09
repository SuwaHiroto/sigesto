<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoTrabajoItemsFeedback extends Model
{
    protected $table = 'tipo_trabajo_items_feedback';

    protected $fillable = [
        'id_tipo_trabajo',
        'id_item',
        'veces_incluido',
    ];

    public function tipoTrabajo(): BelongsTo
    {
        return $this->belongsTo(TipoTrabajo::class, 'id_tipo_trabajo');
    }

    public function itemCatalogo(): BelongsTo
    {
        return $this->belongsTo(ItemCatalogo::class, 'id_item');
    }
}