<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles'; // La tabla se llama 'roles' (sin typo)
    protected $primaryKey = 'id_rol';
    public $timestamps = false; // No tiene created_at/updated_at

    protected $fillable = ['nombre'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'id_rol');
    }
}

/*
    ],
    "aliases": [],
    "minimum-stability": "stable",
    "stability-flags": {},
    "prefer-stable": true,
    "prefer-lowest": false,
    "platform": {
        "php": "^8.4"
    },
    "platform-dev": {},
    "plugin-api-version": "2.9.0"
}


*/