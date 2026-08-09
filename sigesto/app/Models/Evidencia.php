<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evidencia extends Model
{
    use softDeletes;

    protected $table = 'evidencias';
    protected $primaryKey = 'id_evidencia';
    public $timestamps = false;

    protected $fillable = [
        'uuid_solicitud',
        'tipo_evidencia',
        'observaciones',
        'url_archivo'
    ];

    protected $casts = ['fecha_subida' => 'datetime'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'uuid_solicitud', 'uuid_solicitud');
    }
}
