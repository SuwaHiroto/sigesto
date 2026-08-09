<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use SoftDeletes;

    public $timestamps = false;
    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'uuid_solicitud',
        'id_usuario_registro',      // ✅ NUEVO: Quien registró el pago
        'monto_pagado',
        'metodo_pago',
        'nro_operacion',
        'estado_pago',
        'url_comprobante',
        'tipo_pago',                // ✅ NUEVO: ADELANTO o FINAL
        'id_usuario_aprobacion',    // ✅ NUEVO: Admin que verificó
        'fecha_aprobacion',         // ✅ NUEVO: Cuándo se verificó
        'fecha_pago',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_aprobacion' => 'datetime',
    ];

    // Relación con la solicitud
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'uuid_solicitud', 'uuid_solicitud');
    }

    // ✅ NUEVO: Relación con el usuario que registró el pago
    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_registro', 'id_usuario');
    }

    // ✅ NUEVO: Relación con el admin que aprobó el pago
    public function usuarioAprobacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_aprobacion', 'id_usuario');
    }
}
