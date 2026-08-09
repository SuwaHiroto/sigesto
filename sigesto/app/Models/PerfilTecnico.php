<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerfilTecnico extends Model
{
    use SoftDeletes;

    protected $table = 'perfiles_tecnicos';
    protected $primaryKey = 'id_tecnico';

    protected $fillable = ['id_usuario', 'dni', 'especialidad', 'disponible'];

    protected $casts = ['disponible' => 'boolean'];

    public $timestamps = false;

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'id_tecnico', 'id_tecnico');
    }
}
