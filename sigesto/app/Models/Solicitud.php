<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Solicitud extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'solicitudes';
    protected $primaryKey = 'uuid_solicitud';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_cliente',
        'id_tecnico',
        'estado',
        'descripcion_problema',
        'direccion_servicio',
        'es_urgente',
        'fecha_preferida',
        'hora_preferida',
        'notas_disponibilidad',
        'materiales_cliente', // ✅ AGREGAR ESTO
        'latitud',   // ✅ NUEVO
        'longitud',  // ✅ NUEVO
        'fecha_coordinada',
        'hora_coordinada',
        'notas_coordinacion',
    ];

    protected $casts = [
        'fecha_preferida' => 'date',
        'fecha_coordinada' => 'date',
        'items_solicitados' => 'array',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(PerfilCliente::class, 'id_cliente', 'id_cliente');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(PerfilTecnico::class, 'id_tecnico', 'id_tecnico');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'uuid_solicitud', 'uuid_solicitud');
    }

    public function cotizacion(): HasOne
    {
        return $this->hasOne(Cotizacion::class, 'uuid_solicitud', 'uuid_solicitud');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class, 'uuid_solicitud', 'uuid_solicitud');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'uuid_solicitud', 'uuid_solicitud');
    }
}
