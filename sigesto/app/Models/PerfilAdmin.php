<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilAdmin extends Model
{
    protected $table = 'perfiles_admin';
    protected $primaryKey = 'id_admin';
    public $timestamps = false;

    protected $fillable = ['id_usuario'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
