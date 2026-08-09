<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_admin', function (Blueprint $table) {
            $table->id('id_admin');
            $table->foreignId('id_usuario')->unique()->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_admin');
    }
};
