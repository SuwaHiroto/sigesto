<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ CORREGIDO: DROP previo para evitar error "already exists"
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_recalcular_totales_cotizacion');
        DB::unprepared('
            CREATE PROCEDURE sp_recalcular_totales_cotizacion(IN p_id_cotizacion BIGINT UNSIGNED)
            BEGIN
                DECLARE v_subtotal DECIMAL(12,2);
                DECLARE v_tasa_igv DECIMAL(5,2);

                SELECT IFNULL(SUM(subtotal),0) INTO v_subtotal FROM detalle_cotizacion WHERE id_cotizacion = p_id_cotizacion;
                SELECT tasa_igv INTO v_tasa_igv FROM cotizaciones WHERE id_cotizacion = p_id_cotizacion;

                UPDATE cotizaciones
                SET subtotal = v_subtotal, igv = ROUND(v_subtotal * (v_tasa_igv / 100), 2), total = ROUND(v_subtotal * (1 + (v_tasa_igv / 100)), 2)
                WHERE id_cotizacion = p_id_cotizacion;
            END;
        ');

        // ✅ CORREGIDO: DROP previo para los triggers también
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_cotizacion_ai');
        DB::unprepared('
            CREATE TRIGGER trg_detalle_cotizacion_ai AFTER INSERT ON detalle_cotizacion FOR EACH ROW
            BEGIN CALL sp_recalcular_totales_cotizacion(NEW.id_cotizacion); END;
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_cotizacion_au');
        DB::unprepared('
            CREATE TRIGGER trg_detalle_cotizacion_au AFTER UPDATE ON detalle_cotizacion FOR EACH ROW
            BEGIN CALL sp_recalcular_totales_cotizacion(NEW.id_cotizacion); END;
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_cotizacion_ad');
        DB::unprepared('
            CREATE TRIGGER trg_detalle_cotizacion_ad AFTER DELETE ON detalle_cotizacion FOR EACH ROW
            BEGIN CALL sp_recalcular_totales_cotizacion(OLD.id_cotizacion); END;
        ');

    }
        /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_cotizacion_ai');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_cotizacion_au');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_detalle_cotizacion_ad');
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_recalcular_totales_cotizacion');
    }
};
