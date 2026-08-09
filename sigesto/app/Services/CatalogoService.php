<?php

namespace App\Services;

use App\Models\ItemCatalogo;
use App\Models\DetalleCotizacion;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class CatalogoService
{
    /**
     * Lista los items del catálogo.
     */
    public function listarItems(bool $soloActivos = true, bool $incluirInactivos = false): Collection
    {
        $query = ItemCatalogo::query();

        if ($soloActivos && !$incluirInactivos) {
            $query->where('activo', true);
        }

        return $query->orderBy('nombre', 'asc')->get();
    }

    public function crearItem(array $datos): ItemCatalogo
    {
        if (!isset($datos['activo'])) {
            $datos['activo'] = true;
        }
        return ItemCatalogo::create($datos);
    }

    public function actualizarItem(int $id, array $datos): ItemCatalogo
    {
        $item = ItemCatalogo::findOrFail($id);
        $item->update($datos);
        return $item->fresh();
    }

    /**
     * Dar de baja un item (usa campo activo, no SoftDeletes)
     */
    public function eliminarItem(int $id): bool
    {
        $item = ItemCatalogo::findOrFail($id);

        // Verificar si el item fue usado en alguna cotización
        $fueUsado = DetalleCotizacion::where('id_item', $id)->exists();

        if ($fueUsado) {
            // Si fue usado, solo desactivar (preserva integridad histórica)
            return $item->update(['activo' => false]);
        } else {
            // Si nunca fue usado, puede desactivarse
            return $item->update(['activo' => false]);
        }
    }

    /**
     * ✅ NUEVO: Reactivar un item dado de baja
     */
    public function reactivarItem(int $id): ItemCatalogo
    {
        $item = ItemCatalogo::findOrFail($id);

        // Verificar que esté inactivo
        if ($item->activo) {
            throw new \Exception('El item ya está activo.');
        }

        // Reactivar el item
        $item->update(['activo' => true]);

        return $item->fresh();
    }

    public function buscar(string $query): Collection
    {
        return ItemCatalogo::where('activo', true)
            ->where(function ($q) use ($query) {
                $q->where('nombre', 'LIKE', "%{$query}%")
                    ->orWhere('sku_codigo', 'LIKE', "%{$query}%");
            })
            ->orderBy('nombre', 'asc')
            ->get();
    }

    /**
     * Vincular material a un servicio
     */
    public function vincularMaterial(int $idServicio, int $idMaterial, float $cantidad = 1.00): array
    {
        $servicio = ItemCatalogo::findOrFail($idServicio);

        // Validar que sean del tipo correcto (opcional pero recomendado)
        if ($servicio->tipo_item !== 'SERVICIO') {
            throw new Exception('El item principal debe ser de tipo SERVICIO.');
        }

        $material = ItemCatalogo::findOrFail($idMaterial);
        if ($material->tipo_item !== 'MATERIAL') {
            throw new Exception('El item vinculado debe ser de tipo MATERIAL.');
        }

        // Attach o Update
        $servicio->materialesRequeridos()->syncWithoutDetaching([
            $idMaterial => ['cantidad_sugerida' => $cantidad]
        ]);

        return [
            'mensaje' => 'Material vinculado exitosamente al servicio.',
            'data' => [
                'servicio' => $servicio->nombre,
                'material' => $material->nombre,
                'cantidad_sugerida' => $cantidad
            ]
        ];
    }

    /**
     * Obtener materiales de un servicio (para el técnico)
     */
    public function obtenerMaterialesDeServicio(int $idServicio): array
    {
        $servicio = ItemCatalogo::with('materialesRequeridos')->findOrFail($idServicio);

        return [
            'servicio' => $servicio->nombre,
            'materiales' => $servicio->materialesRequeridos->map(fn($m) => [
                'id_item' => $m->id_item,
                'nombre' => $m->nombre,
                'sku' => $m->sku_codigo,
                'unidad' => $m->unidad_medida,
                'precio_ref' => $m->precio_ref,
                'cantidad_sugerida' => $m->pivot->cantidad_sugerida
            ])
        ];
    }
}
