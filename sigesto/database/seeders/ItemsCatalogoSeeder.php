<?php

namespace Database\Seeders;

use App\Models\ItemCatalogo;
use Illuminate\Database\Seeder;

class ItemsCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // MATERIALES
            [
                'sku_codigo' => 'MAT001',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Cable THW 2.5mm',
                'descripcion' => 'Cable de cobre THW calibre 2.5mm, uso residencial',
                'unidad_medida' => 'metro',
                'precio_ref' => 2.50,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT002',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Cable THW 4.0mm',
                'descripcion' => 'Cable de cobre THW calibre 4.0mm',
                'unidad_medida' => 'metro',
                'precio_ref' => 4.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT003',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Breaker 20A Bipolar',
                'descripcion' => 'Interruptor termomagnético 20 amperios bipolar',
                'unidad_medida' => 'unidad',
                'precio_ref' => 35.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT004',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Breaker 30A Bipolar',
                'descripcion' => 'Interruptor termomagnético 30 amperios bipolar',
                'unidad_medida' => 'unidad',
                'precio_ref' => 42.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT005',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Tablero 8 Polos',
                'descripcion' => 'Tablero de distribución 8 polos con barra de tierra',
                'unidad_medida' => 'unidad',
                'precio_ref' => 280.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT006',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Tablero 12 Polos',
                'descripcion' => 'Tablero de distribución 12 polos con barra de tierra',
                'unidad_medida' => 'unidad',
                'precio_ref' => 350.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT007',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Tomacorriente Doble',
                'descripcion' => 'Tomacorriente doble polarizado con placa decorativa',
                'unidad_medida' => 'unidad',
                'precio_ref' => 8.50,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT008',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Interruptor Simple',
                'descripcion' => 'Interruptor simple con placa decorativa blanca',
                'unidad_medida' => 'unidad',
                'precio_ref' => 6.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT009',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Foco LED 9W',
                'descripcion' => 'Foco LED 9W luz blanca 6500K',
                'unidad_medida' => 'unidad',
                'precio_ref' => 12.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT010',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Foco LED 15W',
                'descripcion' => 'Foco LED 15W luz blanca 6500K',
                'unidad_medida' => 'unidad',
                'precio_ref' => 18.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT011',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Caja de Derivación',
                'descripcion' => 'Caja de derivación rectangular PVC 4x4',
                'unidad_medida' => 'unidad',
                'precio_ref' => 6.50,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT012',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Tubería Conduit 3/4"',
                'descripcion' => 'Tubería PVC conduit rígida 3/4 pulgadas',
                'unidad_medida' => 'tramo',
                'precio_ref' => 12.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT013',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Cinta Aislante 3M',
                'descripcion' => 'Cinta aislante eléctrica negra 18 metros',
                'unidad_medida' => 'rollo',
                'precio_ref' => 4.50,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'MAT014',
                'tipo_item' => 'MATERIAL',
                'nombre' => 'Canalización 20mm',
                'descripcion' => 'Canaleta PVC autoadhesiva 20mm x 2 metros',
                'unidad_medida' => 'tramo',
                'precio_ref' => 8.00,
                'activo' => 1,
            ],

            // SERVICIOS
            [
                'sku_codigo' => 'SRV001',
                'tipo_item' => 'SERVICIO',
                'nombre' => 'Mano de Obra: Instalación',
                'descripcion' => 'Servicio de instalación eléctrica completa',
                'unidad_medida' => 'servicio',
                'precio_ref' => 500.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'SRV002',
                'tipo_item' => 'SERVICIO',
                'nombre' => 'Mantenimiento Preventivo',
                'descripcion' => 'Revisión y mantenimiento preventivo de instalaciones',
                'unidad_medida' => 'servicio',
                'precio_ref' => 120.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'SRV003',
                'tipo_item' => 'SERVICIO',
                'nombre' => 'Diagnóstico Eléctrico',
                'descripcion' => 'Evaluación completa del sistema eléctrico con informe',
                'unidad_medida' => 'servicio',
                'precio_ref' => 80.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'SRV004',
                'tipo_item' => 'SERVICIO',
                'nombre' => 'Reparación de Cortocircuito',
                'descripcion' => 'Diagnóstico y reparación de fallas eléctricas',
                'unidad_medida' => 'servicio',
                'precio_ref' => 250.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'SRV005',
                'tipo_item' => 'SERVICIO',
                'nombre' => 'Instalación de Punto Eléctrico',
                'descripcion' => 'Instalación de tomacorriente o interruptor',
                'unidad_medida' => 'punto',
                'precio_ref' => 35.00,
                'activo' => 1,
            ],
            [
                'sku_codigo' => 'SRV006',
                'tipo_item' => 'SERVICIO',
                'nombre' => 'Recableado Completo',
                'descripcion' => 'Reemplazo total de cableado en ambiente',
                'unidad_medida' => 'servicio',
                'precio_ref' => 800.00,
                'activo' => 1,
            ],
        ];

        foreach ($items as $item) {
            ItemCatalogo::create($item);
        }

        $this->command->info('Items del catálogo creados exitosamente.');
    }
}