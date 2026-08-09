<?php

namespace App\Http\Controllers\CatalogoService;

use App\Services\TipoTrabajoService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class TipoTrabajoController extends Controller
{
    public function __construct(
        protected TipoTrabajoService $tipoTrabajoService
    ) {}

    /**
     * GET /api/catalogo/tipos-trabajo
     */
    public function index(): JsonResponse
    {
        try {
            $resultado = $this->tipoTrabajoService->listarTiposTrabajo();
            return response()->json([
                'mensaje' => 'Tipos de trabajo obtenidos exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener tipos de trabajo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/catalogo/tipos-trabajo/{id}/materiales-sugeridos
     */
    public function materialesSugeridos(int $id): JsonResponse
    {
        try {
            $resultado = $this->tipoTrabajoService->obtenerMaterialesSugeridos($id);
            return response()->json([
                'mensaje' => 'Materiales sugeridos obtenidos exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener sugerencias.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ✅ NUEVO: GET /api/catalogo/tipos-trabajo/{id}/estadisticas
     * Muestra al admin cómo está aprendiendo el sistema de este tipo de trabajo
     */
    public function estadisticas(int $id): JsonResponse
    {
        try {
            $resultado = $this->tipoTrabajoService->obtenerEstadisticasAprendizaje($id);
            return response()->json([
                'mensaje' => 'Estadísticas de aprendizaje obtenidas.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener estadísticas.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}