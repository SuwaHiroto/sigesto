<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            
            $table->foreignId('id_rol')
                  ->constrained('roles', 'id_rol')
                  ->restrictOnDelete();
            
            $table->string('email', 150)->unique();
            
            $table->string('password_hash', 255);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};