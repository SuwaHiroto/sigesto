<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id('id_cotizacion');
            $table->foreignUuid('uuid_solicitud')->constrained('solicitudes', 'uuid_solicitud')->cascadeOnDelete();

            // ✅ INTEGRADO: Incluye LIQUIDADA desde el inicio
            $table->enum('estado', ['BORRADOR', 'ENVIADA', 'APROBADA', 'RECHAZADA', 'LIQUIDADA'])->default('BORRADOR');

            $table->decimal('tasa_igv', 5, 2)->default(18.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('igv', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->foreignId('id_usuario_creador')->constrained('usuarios', 'id_usuario');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('estado', 'idx_estado_cotizacion');
            $table->index('uuid_solicitud', 'idx_solicitud_cotizacion');
            $table->index('id_usuario_creador', 'idx_creador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
