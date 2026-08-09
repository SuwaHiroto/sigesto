<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista de Solicitudes
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_recalcular_totales_cotizacion');

        DB::unprepared('
            CREATE OR REPLACE VIEW vw_solicitudes_resumen AS
            SELECT
                s.uuid_solicitud,
                s.estado,
                s.created_at,
                c.id_cliente,
                ucli.nombres AS cliente_nombres,
                ucli.apellidos AS cliente_apellidos,
                t.id_tecnico,
                utec.nombres AS tecnico_nombres,
                utec.apellidos AS tecnico_apellidos
            FROM solicitudes s
            INNER JOIN perfiles_clientes c ON c.id_cliente = s.id_cliente
            INNER JOIN usuarios ucli ON ucli.id_usuario = c.id_usuario
            LEFT JOIN perfiles_tecnicos t ON t.id_tecnico = s.id_tecnico
            LEFT JOIN usuarios utec ON utec.id_usuario = t.id_usuario;
        ');

        // Vista de Cotizaciones
        DB::unprepared('
            CREATE OR REPLACE VIEW vw_cotizaciones_resumen AS
            SELECT
                c.id_cotizacion,
                c.uuid_solicitud,
                c.estado,
                c.subtotal,
                c.igv,
                c.total,
                c.created_at
            FROM cotizaciones c;
        ');

        // Vista de Pagos
        DB::unprepared('
            CREATE OR REPLACE VIEW vw_pagos_resumen AS
            SELECT
                p.id_pago,
                p.uuid_solicitud,
                p.monto_pagado,
                p.metodo_pago,
                p.estado_pago,
                p.fecha_pago
            FROM pagos p;
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vw_solicitudes_resumen');
        DB::unprepared('DROP VIEW IF EXISTS vw_cotizaciones_resumen');
        DB::unprepared('DROP VIEW IF EXISTS vw_pagos_resumen');
    }
};
