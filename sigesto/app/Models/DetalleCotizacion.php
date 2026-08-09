<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCotizacion extends Model
{
    protected $table = 'detalle_cotizacion';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = ['id_cotizacion', 'id_item', 'cantidad', 'precio_aplicado'];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_aplicado' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion', 'id_cotizacion');
    }

    public function itemCatalogo(): BelongsTo
    {
        return $this->belongsTo(ItemCatalogo::class, 'id_item', 'id_item');
    }
}
