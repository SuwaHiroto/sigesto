<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_rol',
        'email',
        'password_hash',
        'nombres',
        'apellidos'
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    //  CRÍTICO: Le decimos a Laravel que use 'password_hash' para autenticarse
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function perfilAdmin(): HasOne
    {
        return $this->hasOne(PerfilAdmin::class, 'id_usuario', 'id_usuario');
    }

    public function perfilTecnico(): HasOne
    {
        return $this->hasOne(PerfilTecnico::class, 'id_usuario', 'id_usuario');
    }

    public function perfilCliente(): HasOne
    {
        return $this->hasOne(PerfilCliente::class, 'id_usuario', 'id_usuario');
    }
}
