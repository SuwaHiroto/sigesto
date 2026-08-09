<?php

namespace Database\Seeders;

use App\Models\TipoTrabajo;
use Illuminate\Database\Seeder;

class TiposTrabajoSeeder extends Seeder
{
    public function run(): void
    {
        $tiposTrabajo = [
            [
                'nombre' => 'Instalación de Tablero Eléctrico',
                'descripcion' => 'Instalación completa de tablero de distribución con breakers',
                'activo' => 1,
            ],
            [
                'nombre' => 'Instalación de Tomacorrientes',
                'descripcion' => 'Instalación de puntos eléctricos y tomacorrientes',
                'activo' => 1,
            ],
            [
                'nombre' => 'Mantenimiento Preventivo',
                'descripcion' => 'Revisión y mantenimiento preventivo de instalaciones eléctricas',
                'activo' => 1,
            ],
            [
                'nombre' => 'Reparación de Cortocircuito',
                'descripcion' => 'Diagnóstico y reparación de fallas eléctricas',
                'activo' => 1,
            ],
            [
                'nombre' => 'Instalación de Iluminación LED',
                'descripcion' => 'Instalación de focos y luminarias LED',
                'activo' => 1,
            ],
            [
                'nombre' => 'Recableado de Ambiente',
                'descripcion' => 'Reemplazo completo de cableado en un ambiente',
                'activo' => 1,
            ],
        ];

        foreach ($tiposTrabajo as $tipo) {
            TipoTrabajo::create($tipo);
        }

        $this->command->info('Tipos de trabajo creados exitosamente.');
    }
}