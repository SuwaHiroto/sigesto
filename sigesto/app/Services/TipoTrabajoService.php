<?php

namespace App\Services;

use App\Models\TipoTrabajo;
use App\Models\TipoTrabajoItemsFeedback;
use App\Models\TipoTrabajoItemsSugeridos;
use Illuminate\Support\Facades\DB;
use Exception;

class TipoTrabajoService
{
    /**
     * Listar todos los tipos de trabajo activos
     */
    public function listarTiposTrabajo(): array
    {
        $tipos = TipoTrabajo::where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id, // o $t->id_tipo_trabajo si tu PK se llama así
                'nombre' => $t->nombre,
                'descripcion' => $t->descripcion,
            ]);

        return [
            'total' => $tipos->count(),
            'tipos_trabajo' => $tipos->toArray(),
        ];
    }

    /**
     * Obtener materiales y servicios sugeridos para un tipo de trabajo
     * ✅ CORREGIDO para funcionar con belongsToMany y pivot
     */
    public function obtenerMaterialesSugeridos(int $idTipoTrabajo): array
    {
        // Cargamos la relación belongsToMany directamente
        $tipoTrabajo = TipoTrabajo::with(['itemsSugeridos' => function ($query) {
            $query->where('items_catalogo.activo', true);
        }])->findOrFail($idTipoTrabajo);

        if (!$tipoTrabajo->activo) {
            throw new Exception('Este tipo de trabajo no está disponible.');
        }

        // $item YA es el modelo ItemCatalogo. Los datos de la pivote están en $item->pivot
        $itemsSugeridos = $tipoTrabajo->itemsSugeridos->map(function ($item) {
            return [
                'id_item' => $item->id, // Cambia a $item->id_item si esa es tu clave primaria
                'sku_codigo' => $item->sku_codigo,
                'nombre' => $item->nombre,
                'tipo_item' => $item->tipo_item,
                'precio_ref' => $item->precio_ref,
                'unidad_medida' => $item->pivot->unidad_medida,       // ✅ Desde el pivot
                'cantidad_sugerida' => $item->pivot->cantidad_sugerida, // ✅ Desde el pivot
                'subtotal_sugerido' => round($item->precio_ref * $item->pivot->cantidad_sugerida, 2),
            ];
        });

        return [
            'tipo_trabajo' => [
                'id' => $tipoTrabajo->id,
                'nombre' => $tipoTrabajo->nombre,
            ],
            'total_sugerido' => round($itemsSugeridos->sum('subtotal_sugerido'), 2),
            'items_sugeridos' => $itemsSugeridos->values()->toArray(),
        ];
    }

    /**
     * ✅ APRENDIZAJE AUTOMÁTICO: Detecta qué materiales se usan
     */
    public function aprenderDeCotizacion(int $idTipoTrabajo, array $itemsUsados): void
    {
        if (!$idTipoTrabajo) return;

        DB::transaction(function () use ($idTipoTrabajo, $itemsUsados) {
            $idsItemsUsados = collect($itemsUsados)->pluck('id_item')->unique();

            foreach ($idsItemsUsados as $idItem) {
                $feedback = TipoTrabajoItemsFeedback::firstOrCreate(
                    ['id_tipo_trabajo' => $idTipoTrabajo, 'id_item' => $idItem],
                    ['veces_incluido' => 0]
                );

                $feedback->increment('veces_incluido');

                // Auto-sugerencia: Si se usó 3+ veces, agregar a la tabla pivote
                if ($feedback->veces_incluido >= 3) {
                    // Usamos el modelo intermedio para asegurar que se cree la fila en la pivote
                    TipoTrabajoItemsSugeridos::firstOrCreate(
                        ['id_tipo_trabajo' => $idTipoTrabajo, 'id_item' => $idItem],
                        ['cantidad_sugerida' => 1]
                    );
                }
            }
        });
    }

    /**
     * Obtener estadísticas de aprendizaje (Solo Admin)
     */
    public function obtenerEstadisticasAprendizaje(int $idTipoTrabajo): array
    {
        $feedback = TipoTrabajoItemsFeedback::with('itemCatalogo')
            ->where('id_tipo_trabajo', $idTipoTrabajo)
            ->orderByDesc('veces_incluido')
            ->get();

        return [
            'id_tipo_trabajo' => $idTipoTrabajo,
            'total_items_aprendidos' => $feedback->count(),
            'items' => $feedback->map(fn($f) => [
                'id_item' => $f->id_item,
                'nombre' => $f->itemCatalogo->nombre,
                'veces_incluido' => $f->veces_incluido,
                'confianza' => $f->veces_incluido >= 5 ? 'alta' : ($f->veces_incluido >= 3 ? 'media' : 'baja'),
            ])->toArray(),
        ];
    }
}