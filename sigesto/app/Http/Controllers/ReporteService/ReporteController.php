<?php

namespace App\Http\Controllers\ReporteService;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function __construct(
        protected ReporteService $reporteService
    ) {}

    /**
     * GET /api/reportes/dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $resultado = $this->reporteService->obtenerDashboard();
            return response()->json([
                'mensaje' => 'Dashboard obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el dashboard.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reportes/ingresos
     */
    public function ingresos(Request $request): JsonResponse
    {
        try {
            $periodo = $request->query('periodo', 'mes');
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            $resultado = $this->reporteService->obtenerReporteIngresos(
                $periodo,
                $fechaInicio,
                $fechaFin
            );

            return response()->json([
                'mensaje' => 'Reporte de ingresos obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte de ingresos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reportes/solicitudes
     */
    public function solicitudes(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            $resultado = $this->reporteService->obtenerReporteSolicitudes(
                $fechaInicio,
                $fechaFin
            );

            return response()->json([
                'mensaje' => 'Reporte de solicitudes obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte de solicitudes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reportes/tecnicos
     */
    public function tecnicos(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            $resultado = $this->reporteService->obtenerReporteTecnicos(
                $fechaInicio,
                $fechaFin
            );

            return response()->json([
                'mensaje' => 'Reporte de técnicos obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte de técnicos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reportes/cotizaciones
     */
    public function cotizaciones(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');

            $resultado = $this->reporteService->obtenerReporteCotizaciones(
                $fechaInicio,
                $fechaFin
            );

            return response()->json([
                'mensaje' => 'Reporte de cotizaciones obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte de cotizaciones.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reportes/tipos-trabajo
     */
    public function tiposTrabajo(): JsonResponse
    {
        try {
            $resultado = $this->reporteService->obtenerReporteTiposTrabajo();
            return response()->json([
                'mensaje' => 'Reporte de tipos de trabajo obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte de tipos de trabajo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reportes/pagos-pendientes
     */
    public function pagosPendientes(): JsonResponse
    {
        try {
            $resultado = $this->reporteService->obtenerReportePagosPendientes();
            return response()->json([
                'mensaje' => 'Reporte de pagos pendientes obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el reporte de pagos pendientes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * ✅ NUEVO: Dashboard del Técnico
     */
    public function dashboardTecnico(Request $request): JsonResponse
    {
        try {
            $resultado = $this->reporteService->obtenerDashboardTecnico($request->user());

            return response()->json([
                'mensaje' => 'Dashboard de técnico obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el dashboard.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
