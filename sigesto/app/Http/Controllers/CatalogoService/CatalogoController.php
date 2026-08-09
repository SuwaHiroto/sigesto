<?php

namespace App\Http\Controllers\CatalogoService;

use App\Http\Controllers\Controller;
use App\Services\CatalogoService;
use App\Http\Requests\CatalogoService\StoreItemCatalogoRequest;
use App\Http\Requests\CatalogoService\UpdateItemCatalogoRequest;
use App\Http\Requests\CatalogoService\VincularMaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CatalogoController extends Controller
{
    public function __construct(
        protected CatalogoService $catalogoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $todos = $request->boolean('todos', false);
        $verEliminados = $request->boolean('ver_eliminados', false);

        $items = $this->catalogoService->listarItems($todos, $verEliminados);
        return response()->json([
            'mensaje' => 'Catálogo obtenido exitosamente.',
            'data' => $items
        ], 200);
    }

    public function store(StoreItemCatalogoRequest $request): JsonResponse
    {
        try {
            $item = $this->catalogoService->crearItem($request->validated());
            return response()->json([
                'mensaje' => 'Item creado exitosamente.',
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al crear el item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buscar(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $items = $this->catalogoService->buscar($query);
        return response()->json([
            'mensaje' => 'Búsqueda realizada exitosamente.',
            'data' => $items
        ], 200);
    }

    public function update(UpdateItemCatalogoRequest $request, int $id): JsonResponse
    {
        try {
            $item = $this->catalogoService->actualizarItem($id, $request->validated());
            return response()->json([
                'mensaje' => 'Item actualizado exitosamente.',
                'data' => $item
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'El item no existe.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al actualizar el item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->catalogoService->eliminarItem($id);
            return response()->json([
                'mensaje' => 'Item dado de baja del catálogo (activo = false).'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'El item no existe.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al dar de baja.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Reactivar item eliminado lógicamente
     * PATCH /api/catalogo/{id}/reactivar
     */
    public function reactivar(int $id): JsonResponse
    {
        try {
            $item = $this->catalogoService->reactivarItem($id);
            return response()->json([
                'mensaje' => 'Item reactivado exitosamente.',
                'data' => $item
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'El item no existe o ya fue eliminado permanentemente.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al reactivar el item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function vincularMaterial(VincularMaterialRequest $request): JsonResponse
    {
        try {
            $resultado = $this->catalogoService->vincularMaterial(
                $request->id_servicio,
                $request->id_material,
                $request->cantidad_sugerida ?? 1.00
            );

            return response()->json($resultado, 201);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error al vincular', 'error' => $e->getMessage()], 400);
        }
    }

    public function verMaterialesDeServicio(int $idServicio): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->catalogoService->obtenerMaterialesDeServicio($idServicio)
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Servicio no encontrado', 'error' => $e->getMessage()], 404);
        }
    }
}
