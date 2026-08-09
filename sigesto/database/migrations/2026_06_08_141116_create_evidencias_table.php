<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id('id_evidencia');
            $table->foreignUuid('uuid_solicitud')->constrained('solicitudes', 'uuid_solicitud')->cascadeOnDelete();
            $table->enum('tipo_evidencia', ['FOTO_ANTES', 'FOTO_DESPUES', 'COMPROBANTE_PAGO']);
            $table->string('url_archivo', 500);

            // ✅ INTEGRADO: Campo de observaciones
            $table->text('observaciones')->nullable();

            $table->timestamp('fecha_subida')->useCurrent();
            $table->softDeletes(); // ✅ INTEGRADO: Soft deletes

            // Índices
            $table->index('uuid_solicitud', 'idx_evidencia_solicitud');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias');
    }
};
