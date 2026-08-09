<?php

namespace Database\Seeders;

use App\Models\TipoTrabajoItemsSugeridos;
use Illuminate\Database\Seeder;

class TipoTrabajoItemsSugeridosSeeder extends Seeder
{
    public function run(): void
    {
        $relaciones = [
            // Tipo 1: Instalación de Tablero Eléctrico
            ['id_tipo_trabajo' => 1, 'id_item' => 5, 'cantidad_sugerida' => 1],   // Tablero 8 Polos
            ['id_tipo_trabajo' => 1, 'id_item' => 3, 'cantidad_sugerida' => 2],   // Breaker 20A
            ['id_tipo_trabajo' => 1, 'id_item' => 4, 'cantidad_sugerida' => 2],   // Breaker 30A
            ['id_tipo_trabajo' => 1, 'id_item' => 1, 'cantidad_sugerida' => 10],  // Cable 2.5mm
            ['id_tipo_trabajo' => 1, 'id_item' => 13, 'cantidad_sugerida' => 2],  // Cinta Aislante
            ['id_tipo_trabajo' => 1, 'id_item' => 15, 'cantidad_sugerida' => 1],  // MO Instalación

            // Tipo 2: Instalación de Tomacorrientes
            ['id_tipo_trabajo' => 2, 'id_item' => 7, 'cantidad_sugerida' => 1],   // Tomacorriente Doble
            ['id_tipo_trabajo' => 2, 'id_item' => 8, 'cantidad_sugerida' => 1],   // Interruptor Simple
            ['id_tipo_trabajo' => 2, 'id_item' => 1, 'cantidad_sugerida' => 5],   // Cable 2.5mm
            ['id_tipo_trabajo' => 2, 'id_item' => 11, 'cantidad_sugerida' => 2],  // Caja Derivación
            ['id_tipo_trabajo' => 2, 'id_item' => 13, 'cantidad_sugerida' => 1],  // Cinta Aislante
            ['id_tipo_trabajo' => 2, 'id_item' => 19, 'cantidad_sugerida' => 1],  // MO Punto Eléctrico

            // Tipo 3: Mantenimiento Preventivo
            ['id_tipo_trabajo' => 3, 'id_item' => 13, 'cantidad_sugerida' => 1],  // Cinta Aislante
            ['id_tipo_trabajo' => 3, 'id_item' => 16, 'cantidad_sugerida' => 1],  // Mantenimiento Preventivo

            // Tipo 4: Reparación de Cortocircuito
            ['id_tipo_trabajo' => 4, 'id_item' => 3, 'cantidad_sugerida' => 1],   // Breaker 20A
            ['id_tipo_trabajo' => 4, 'id_item' => 1, 'cantidad_sugerida' => 10],  // Cable 2.5mm
            ['id_tipo_trabajo' => 4, 'id_item' => 13, 'cantidad_sugerida' => 1],  // Cinta Aislante
            ['id_tipo_trabajo' => 4, 'id_item' => 17, 'cantidad_sugerida' => 1],  // Diagnóstico
            ['id_tipo_trabajo' => 4, 'id_item' => 18, 'cantidad_sugerida' => 1],  // Reparación

            // Tipo 5: Instalación de Iluminación LED
            ['id_tipo_trabajo' => 5, 'id_item' => 9, 'cantidad_sugerida' => 4],   // Foco LED 9W
            ['id_tipo_trabajo' => 5, 'id_item' => 10, 'cantidad_sugerida' => 4],  // Foco LED 15W
            ['id_tipo_trabajo' => 5, 'id_item' => 8, 'cantidad_sugerida' => 2],   // Interruptor Simple
            ['id_tipo_trabajo' => 5, 'id_item' => 12, 'cantidad_sugerida' => 5],  // Tubería Conduit
            ['id_tipo_trabajo' => 5, 'id_item' => 11, 'cantidad_sugerida' => 2],  // Caja Derivación
            ['id_tipo_trabajo' => 5, 'id_item' => 19, 'cantidad_sugerida' => 1],  // MO Punto Eléctrico

            // Tipo 6: Recableado de Ambiente
            ['id_tipo_trabajo' => 6, 'id_item' => 1, 'cantidad_sugerida' => 50],  // Cable 2.5mm
            ['id_tipo_trabajo' => 6, 'id_item' => 2, 'cantidad_sugerida' => 20],  // Cable 4.0mm
            ['id_tipo_trabajo' => 6, 'id_item' => 12, 'cantidad_sugerida' => 10], // Tubería Conduit
            ['id_tipo_trabajo' => 6, 'id_item' => 11, 'cantidad_sugerida' => 5],  // Caja Derivación
            ['id_tipo_trabajo' => 6, 'id_item' => 13, 'cantidad_sugerida' => 3],  // Cinta Aislante
            ['id_tipo_trabajo' => 6, 'id_item' => 20, 'cantidad_sugerida' => 1],  // Recableado Completo
        ];

        foreach ($relaciones as $relacion) {
            TipoTrabajoItemsSugeridos::create($relacion);
        }

        $this->command->info('Relaciones tipo_trabajo_items_sugeridos creadas exitosamente.');
    }
}