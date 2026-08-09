<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_trabajo_items_sugeridos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_trabajo')->constrained('tipos_trabajo')->onDelete('cascade');
            
            // ✅ CORREGIDO: Especificar que la columna en items_catalogo es 'id_item'
            $table->unsignedBigInteger('id_item');
            $table->foreign('id_item')
                  ->references('id_item')  // ✅ Columna correcta en items_catalogo
                  ->on('items_catalogo')
                  ->onDelete('cascade');
            
            $table->decimal('cantidad_sugerida', 10, 2)->default(1);
            $table->string('unidad_medida', 20)->nullable();
            $table->boolean('obligatorio')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_trabajo_items_sugeridos');
    }
};  