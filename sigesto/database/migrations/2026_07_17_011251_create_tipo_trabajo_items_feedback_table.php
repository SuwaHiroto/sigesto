<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_trabajo_items_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_tipo_trabajo');
            $table->unsignedBigInteger('id_item');
            $table->integer('veces_incluido')->default(0);
            $table->timestamps();

            $table->foreign('id_tipo_trabajo')->references('id')->on('tipos_trabajo')->onDelete('cascade');
            $table->foreign('id_item')->references('id_item')->on('items_catalogo')->onDelete('cascade');
            $table->unique(['id_tipo_trabajo', 'id_item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_trabajo_items_feedback');
    }
};