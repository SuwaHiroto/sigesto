<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VwSolicitudResumen extends Model
{
    protected $table = 'vw_solicitudes_resumen';
    public $timestamps = false; // Las vistas no tienen timestamps automáticos

    // Desactivamos inserciones/actualizaciones porque es una vista de solo lectura
    public $incrementing = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
