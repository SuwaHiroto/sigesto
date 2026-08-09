<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->uuid('uuid_solicitud')->primary();
            $table->foreignId('id_cliente')->constrained('perfiles_clientes', 'id_cliente');
            $table->foreignId('id_tecnico')->nullable()->constrained('perfiles_tecnicos', 'id_tecnico');
            $table->enum('estado', [
                'PENDIENTE',
                'ASIGNADA',
                'COTIZADA',
                'REVISION_PAGO',
                'APROBADA',
                'RECHAZADA',
                'EN_PROCESO',
                'FINALIZADA',
                'PAGADA',
                'CANCELADA'
            ]);
            $table->text('descripcion_problema');
            $table->string('direccion_servicio', 255);

            // ✅ INTEGRADO: Preferencias del cliente
            $table->date('fecha_preferida')->nullable();
            $table->time('hora_preferida')->nullable();
            $table->string('notas_disponibilidad', 500)->nullable();

            // ✅ INTEGRADO: Campo de urgencia
            $table->boolean('es_urgente')->default(false);

            // ✅ INTEGRADO: Materiales que el cliente ya tiene
            $table->text('materiales_cliente')->nullable();

            // ✅ INTEGRADO: Coordinación con técnico
            $table->date('fecha_coordinada')->nullable();
            $table->time('hora_coordinada')->nullable();
            $table->string('notas_coordinacion', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('estado', 'idx_estado_solicitud');
            $table->index('id_tecnico', 'idx_tecnico');
            $table->index('id_cliente', 'idx_cliente');
            $table->index('created_at', 'idx_fecha_creacion');
            $table->index(['estado', 'id_tecnico'], 'idx_estado_tecnico');
        });

        // ✅ Crear Stored Procedure para validar conflictos de coordinación
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_verificar_conflicto_coordinacion');
        DB::unprepared('
            CREATE PROCEDURE sp_verificar_conflicto_coordinacion(
                IN p_id_tecnico BIGINT UNSIGNED,
                IN p_fecha_coordinada DATE,
                IN p_hora_coordinada TIME,
                IN p_uuid_solicitud_excluir CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            )
            BEGIN
                DECLARE v_conflicto_existe INT DEFAULT 0;
                DECLARE v_uuid_conflicto CHAR(36);
                DECLARE v_hora_conflicto TIME;
                DECLARE v_direccion_conflicto VARCHAR(255);

                SET @hora_inicio = DATE_SUB(p_hora_coordinada, INTERVAL 1 HOUR);
                SET @hora_fin = DATE_ADD(p_hora_coordinada, INTERVAL 1 HOUR);

                SELECT COUNT(*), MAX(uuid_solicitud), MAX(hora_coordinada), MAX(direccion_servicio)
                INTO v_conflicto_existe, v_uuid_conflicto, v_hora_conflicto, v_direccion_conflicto
                FROM solicitudes
                WHERE id_tecnico = p_id_tecnico
                    AND fecha_coordinada = p_fecha_coordinada
                    AND hora_coordinada IS NOT NULL
                    AND hora_coordinada BETWEEN @hora_inicio AND @hora_fin
                    AND (p_uuid_solicitud_excluir IS NULL OR uuid_solicitud COLLATE utf8mb4_unicode_ci != p_uuid_solicitud_excluir COLLATE utf8mb4_unicode_ci)
                    AND estado IN (\'ASIGNADA\', \'EN_PROCESO\', \'COTIZADA\', \'REVISION_PAGO\', \'APROBADA\')
                    AND deleted_at IS NULL;

                IF v_conflicto_existe > 0 THEN
                    SELECT 1 AS tiene_conflicto, v_uuid_conflicto AS uuid_solicitud_conflicto,
                        v_hora_conflicto AS hora_coordinada_conflicto, v_direccion_conflicto AS direccion_conflicto,
                        CONCAT(\'El técnico ya tiene una visita coordinada a las \', TIME_FORMAT(v_hora_conflicto, \'%H:%i\'), \' del mismo día en \', v_direccion_conflicto) AS mensaje_conflicto;
                ELSE
                    SELECT 0 AS tiene_conflicto, NULL AS uuid_solicitud_conflicto, NULL AS hora_coordinada_conflicto,
                        NULL AS direccion_conflicto, \'No hay conflictos de coordinación\' AS mensaje_conflicto;
                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_verificar_conflicto_coordinacion');
        Schema::dropIfExists('solicitudes');
    }
};
