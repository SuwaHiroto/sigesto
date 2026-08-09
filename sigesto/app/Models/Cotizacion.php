<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cotizacion extends Model
{
    use SoftDeletes;

    protected $table = 'cotizaciones';
    protected $primaryKey = 'id_cotizacion';

    // No incluimos subtotal, igv, total en fillable (los calcula el Trigger/SP)
    protected $fillable = [
        'uuid_solicitud',
        'estado',
        'tasa_igv',
        'id_usuario_creador'
    ];

    protected $casts = [
        'tasa_igv' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'igv'      => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'uuid_solicitud', 'uuid_solicitud');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creador', 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCotizacion::class, 'id_cotizacion', 'id_cotizacion');
    }
}
