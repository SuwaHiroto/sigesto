<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerfilCliente extends Model
{
    use SoftDeletes;

    protected $table = 'perfiles_clientes';
    protected $primaryKey = 'id_cliente';

    protected $fillable = ['id_usuario', 'dni_ruc', 'telefono', 'direccion'];

    public $timestamps = false;

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'id_cliente', 'id_cliente');
    }
}
