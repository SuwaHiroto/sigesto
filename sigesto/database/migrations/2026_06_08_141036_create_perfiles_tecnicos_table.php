<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_tecnicos', function (Blueprint $table) {
            $table->id('id_tecnico');
            $table->foreignId('id_usuario')->unique()->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $table->char('dni', 8)->unique();
            $table->string('especialidad', 100)->nullable();
            $table->boolean('disponible')->default(true);
            $table->softDeletes();

            $table->index('disponible', 'idx_tecnico_disponible');
            $table->index('especialidad', 'idx_tecnico_especialidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_tecnicos');
    }
};
