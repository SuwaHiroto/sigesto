<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados', function (Blueprint $table) {
            $table->id('id_historial');
            $table->foreignUuid('uuid_solicitud')->constrained('solicitudes', 'uuid_solicitud')->cascadeOnDelete();
            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50);
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->foreignId('id_usuario_accion')->constrained('usuarios', 'id_usuario');

            $table->index('uuid_solicitud', 'idx_historial_solicitud');
            $table->index('fecha_cambio', 'idx_fecha_cambio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados');
    }
};
