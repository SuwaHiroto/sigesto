<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstado extends Model
{
    protected $table = 'historial_estados';
    protected $primaryKey = 'id_historial';
    public $timestamps = false; // Usa 'fecha_cambio' en su lugar

    protected $fillable = [
        'uuid_solicitud',
        'estado_anterior',
        'estado_nuevo',
        'fecha_cambio',
        'id_usuario_accion'
    ];

    protected $casts = ['fecha_cambio' => 'datetime'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'uuid_solicitud', 'uuid_solicitud');
    }

    public function usuarioAccion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_accion', 'id_usuario');
    }
}
