<?php

namespace App\Http\Controllers\EvidenciaService;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvidenciaService\StoreEvidenciaRequest;
use App\Services\EvidenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EvidenciaController extends Controller
{
    public function __construct(
        protected EvidenciaService $evidenciaService
    ) {}

    /**
     * ✅ UNIFICADO: POST /api/evidencias/subir
     */
    public function store(StoreEvidenciaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            if ($request->hasFile('archivos')) {
                $datos = [
                    'uuid_solicitud' => $validated['uuid_solicitud'],
                    'tipos_evidencia' => $validated['tipos_evidencia'],
                    'observaciones' => $validated['observaciones'] ?? null,
                ];
                
                $resultado = $this->evidenciaService->subirEvidencia(
                    $datos,
                    $request->file('archivos')
                );
            } else {
                $resultado = $this->evidenciaService->subirEvidencia(
                    $validated,
                    $request->file('archivo')
                );
            }

            return response()->json([
                'mensaje' => 'Evidencia(s) subida(s) exitosamente.',
                'data' => $resultado
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'mensaje' => 'Error de validación en los datos enviados.',
                'errores' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Solicitud no encontrada.',
                'error' => 'El UUID proporcionado no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al subir la(s) evidencia(s).',
                'error' => $e->getMessage()
            ], 500); // El frontend de Flutter capturará este mensaje exacto
        }
    }

    public function porSolicitud(string $uuid): JsonResponse
    {
        try {
            $resultado = $this->evidenciaService->obtenerEvidenciasPorSolicitud($uuid);
            return response()->json([
                'mensaje' => 'Evidencias obtenidas exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Solicitud no encontrada.',
                'error' => 'El UUID proporcionado no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener las evidencias.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->evidenciaService->eliminarEvidencia($id);
            return response()->json([
                'mensaje' => 'Evidencia eliminada exitosamente.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Evidencia no encontrada.',
                'error' => 'El ID proporcionado no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al eliminar la evidencia.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}