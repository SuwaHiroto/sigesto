<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_catalogo', function (Blueprint $table) {
            $table->id('id_item');
            $table->string('sku_codigo', 50)->unique()->nullable();
            $table->enum('tipo_item', ['MATERIAL', 'SERVICIO']);
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('unidad_medida', 20)->nullable();
            $table->decimal('precio_ref', 10, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo_item', 'idx_tipo_item');
            $table->index('nombre', 'idx_nombre');
            $table->index('activo', 'idx_activo');
        });

        DB::statement('ALTER TABLE items_catalogo ADD CONSTRAINT chk_precio_ref CHECK (precio_ref >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('items_catalogo');
    }
};
