<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pago');
            $table->foreignUuid('uuid_solicitud')->constrained('solicitudes', 'uuid_solicitud');
            $table->foreignId('id_usuario_registro')->constrained('usuarios', 'id_usuario');

            $table->decimal('monto_pagado', 12, 2);
            $table->enum('metodo_pago', ['EFECTIVO', 'YAPE', 'PLIN', 'TRANSFERENCIA', 'TARJETA']);
            $table->string('nro_operacion', 100)->nullable();
            $table->enum('estado_pago', ['PENDIENTE_APROBACION', 'COMPLETADO', 'RECHAZADO', 'REEMBOLSADO'])->default('PENDIENTE_APROBACION');
            $table->string('url_comprobante', 500)->nullable();

            // ✅ NUEVOS CAMPOS para el flujo de adelantos
            $table->enum('tipo_pago', ['ADELANTO', 'FINAL'])->default('FINAL');
            $table->foreignId('id_usuario_aprobacion')->nullable()->constrained('usuarios', 'id_usuario');
            $table->timestamp('fecha_aprobacion')->nullable();

            $table->timestamp('fecha_pago')->useCurrent();
            $table->softDeletes();

            // Índices
            $table->index('uuid_solicitud', 'idx_pago_solicitud');
            $table->index('estado_pago', 'idx_estado_pago');
            $table->index('fecha_pago', 'idx_fecha_pago');
            $table->index('tipo_pago', 'idx_tipo_pago'); // ✅ AGREGADO
        });

        DB::statement('ALTER TABLE pagos ADD CONSTRAINT chk_monto_pagado CHECK (monto_pagado > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
