<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // ✅ Solo agregar coordenadas GPS después de direccion_servicio
            $table->decimal('latitud', 10, 8)->nullable()->after('direccion_servicio');
            $table->decimal('longitud', 11, 8)->nullable()->after('latitud');
            
            // Índice para consultas espaciales
            $table->index(['latitud', 'longitud'], 'idx_ubicacion');
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropIndex('idx_ubicacion');
            $table->dropColumn(['latitud', 'longitud']);
        });
    }
};