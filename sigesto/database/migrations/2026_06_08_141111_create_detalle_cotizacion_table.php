<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_cotizacion', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->foreignId('id_cotizacion')->constrained('cotizaciones', 'id_cotizacion')->cascadeOnDelete();
            $table->foreignId('id_item')->constrained('items_catalogo', 'id_item');

            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_aplicado', 10, 2);
            $table->decimal('subtotal', 12, 2)->storedAs('cantidad * precio_aplicado');

            $table->index('id_cotizacion', 'idx_detalle_cotizacion');
            $table->index('id_item', 'idx_detalle_item');
        });

        DB::statement('ALTER TABLE detalle_cotizacion ADD CONSTRAINT chk_cantidad CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE detalle_cotizacion ADD CONSTRAINT chk_precio CHECK (precio_aplicado >= 0)');

    }

    public function down(): void
    {

        Schema::dropIfExists('detalle_cotizacion');
    }
};
