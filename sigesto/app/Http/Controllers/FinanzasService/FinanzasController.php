<?php

namespace App\Http\Controllers\FinanzasService;

use App\Http\Controllers\Controller;
use App\Services\FinanzasService;
use App\Http\Requests\FinanzasService\RegistrarAdelantoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\FinanzasService\RegistrarPagoRequest;


class FinanzasController extends Controller
{
    public function __construct(
        protected FinanzasService $finanzasService
    ) {}

    /**
     * Registrar pago final
     * POST /api/finanzas/pagar
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(app(RegistrarPagoRequest::class)->rules());

            // ✅ Obtener archivo si viene
            $archivo = $request->file('comprobante');

            $resultado = $this->finanzasService->registrarPago(
                $validated,
                $request->user()->id_usuario,
                $archivo // ✅ Pasar archivo al service
            );

            return response()->json($resultado, 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al registrar pago.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ✅ NUEVO: Listar pagos generales (Solo Admin)
     * GET /api/finanzas
     */
    public function index(): JsonResponse
    {
        try {
            $resultado = $this->finanzasService->listarPagosGenerales();

            return response()->json([
                'mensaje' => 'Reporte financiero obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte financiero.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Ver pagos de una solicitud
     * GET /api/finanzas/solicitud/{uuid}
     */
    public function porSolicitud(string $uuid): JsonResponse
    {
        try {
            $resultado = $this->finanzasService->obtenerPagosPorSolicitud($uuid);

            return response()->json([
                'mensaje' => 'Historial de pagos obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Solicitud no encontrada.',
                'error' => 'El UUID proporcionado no existe en el sistema.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el historial de pagos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * ✅ NUEVO: Obtener datos de la cuenta bancaria
     * GET /api/finanzas/cuenta-bancaria
     */
    public function obtenerCuentaBancaria(): JsonResponse
    {
        $datos = $this->finanzasService->obtenerCuentaBancaria();

        return response()->json([
            'mensaje' => 'Datos de cuenta bancaria obtenidos exitosamente.',
            'data' => $datos
        ], 200);
    }

    /**
     * Cliente registra el adelanto
     * POST /api/finanzas/{uuid}/adelanto
     */
    public function registrarAdelanto(Request $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validate(app(RegistrarAdelantoRequest::class)->rules());

            // ✅ Obtener archivo si viene
            $archivo = $request->file('comprobante');

            $resultado = $this->finanzasService->registrarAdelanto(
                $uuid,
                $validated,
                $request->user(),
                $archivo // ✅ Pasar archivo al service
            );

            return response()->json($resultado, 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al registrar adelanto.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ✅ NUEVO: Admin verifica el pago
     * PATCH /api/finanzas/{id_pago}/verificar
     */
    public function verificarPago(int $id_pago, Request $request): JsonResponse
    {
        try {
            $resultado = $this->finanzasService->verificarPago(
                $id_pago,
                $request->user()
            );

            return response()->json($resultado, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Pago no encontrado.',
                'error' => 'El ID de pago proporcionado no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al verificar el pago.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ✅ NUEVO: Cliente ve todos sus pagos
     * GET /api/finanzas/mis-pagos
     */
    public function misPagos(Request $request): JsonResponse
    {
        try {
            $estado = $request->query('estado'); // Filtro opcional
            $resultado = $this->finanzasService->obtenerMisPagos($request->user(), $estado);

            return response()->json([
                'mensaje' => 'Historial de pagos obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el historial de pagos.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    /**
     * ✅ NUEVO: Obtener resumen de pagos de una solicitud
     * GET /api/finanzas/{uuid}/resumen
     * Consumido por: Técnico (sabe cuánto cobrar) y Cliente (sabe cuánto falta pagar)
     */
    public function resumenPagos(string $uuid): JsonResponse
    {
        try {
            $resultado = $this->finanzasService->obtenerResumenPagos($uuid);

            return response()->json([
                'mensaje' => 'Resumen de pagos obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Solicitud no encontrada.',
                'error' => 'El UUID proporcionado no existe en el sistema.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el resumen de pagos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
 * ✅ NUEVO: Admin rechaza un pago pendiente
 * PATCH /api/finanzas/{id_pago}/rechazar
 */
public function rechazarPago(int $id_pago, Request $request): JsonResponse
{
    try {
        $resultado = $this->finanzasService->rechazarPago($id_pago, $request->user());
        return response()->json($resultado, 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['mensaje' => 'Pago no encontrado.'], 404);
    } catch (\Exception $e) {
        return response()->json(['mensaje' => 'Error al rechazar el pago.', 'error' => $e->getMessage()], 400);
    }
}
}
