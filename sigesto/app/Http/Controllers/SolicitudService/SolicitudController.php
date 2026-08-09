<?php

namespace App\Http\Controllers\SolicitudService;

use App\Http\Controllers\Controller;
use App\Http\Requests\SolicitudService\StoreSolicitudRequest;
use App\Http\Requests\SolicitudService\CambiarEstadoSolicitudRequest;
use App\Http\Requests\SolicitudService\ActualizarSolicitudRequest;
use App\Http\Requests\SolicitudService\AsignarTecnicoRequest;
use App\Services\SolicitudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Solicitud;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    public function __construct(
        protected SolicitudService $solicitudService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $soloUrgentes = $request->query('urgentes') === '1';
        $estado = $request->query('estado');

        $solicitudes = $this->solicitudService->listarSolicitudesFiltradas($soloUrgentes, $estado);

        return response()->json(['data' => $solicitudes], 200);
    }

    public function store(StoreSolicitudRequest $request): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->crearSolicitud(
                $request->validated(),
                $request->user()->id_usuario
            );

            return response()->json([
                'mensaje' => 'Solicitud generada con éxito.',
                'ticket' => $solicitud->uuid_solicitud,
                'data' => $solicitud
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al crear solicitud.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->obtenerDetalle($uuid);
            return response()->json(['data' => $solicitud], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Solicitud no encontrada.'], 404);
        }
    }

    public function update(ActualizarSolicitudRequest $request, string $uuid): JsonResponse
    {
        try {
            $solicitud = $this->solicitudService->actualizarSolicitud(
                $uuid,
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'mensaje' => 'Solicitud actualizada exitosamente.',
                'data' => $solicitud
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Solicitud no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al actualizar solicitud.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->solicitudService->eliminarSolicitud($uuid);

            return response()->json([
                'mensaje' => 'Solicitud dada de baja lógicamente (SoftDelete aplicado).'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Solicitud no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al eliminar solicitud.',
                'error' => $e->getMessage()
            ], 409);
        }
    }

    public function hojaRuta(Request $request): JsonResponse
    {
        try {
            $solicitudes = $this->solicitudService->obtenerHojaRuta($request->user());
            return response()->json(['data' => $solicitudes], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener hoja de ruta.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Asignar Técnico con validación de conflictos
     * Si el cliente propuso una hora, validar si hay conflicto
     */
    public function asignarTecnico(AsignarTecnicoRequest $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // ✅ CORRECCIÓN: Obtener el ID del usuario de forma segura
            $usuario = $request->user();
            $idUsuario = $usuario->id_usuario ?? $usuario->id ?? null;
            
            if (!$idUsuario) {
                return response()->json(['mensaje' => 'Usuario no autenticado correctamente.'], 401);
            }

            $solicitud = $this->solicitudService->asignarTecnico(
                $uuid,
                (int) $validated['id_tecnico'],
                (int) $idUsuario, // Forzamos a int para evitar TypeError
                $validated['fecha_coordinada'] ?? null,
                $validated['hora_coordinada'] ?? null
            );

            return response()->json([
                'mensaje' => 'Técnico asignado exitosamente.',
                'data' => $solicitud
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Solicitud no encontrada.'], 404);
        } catch (\Throwable $e) { // ✅ CAMBIADO: \Throwable atrapa Exception, TypeError, Error, etc.
            // ✅ CORRECCIÓN: Registrar el error exacto en los logs para depuración
            \Log::error('Error al asignar técnico: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'mensaje' => 'Error al asignar técnico.',
                'error' => $e->getMessage()
            ], 409); // Mantenemos 409 para conflictos de negocio
        }
    }

    public function cambiarEstado(CambiarEstadoSolicitudRequest $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validated();

            $solicitud = $this->solicitudService->cambiarEstado(
                $uuid,
                $validated['estado'],
                $request->user(),
                $validated['motivo_rechazo'] ?? null
            );

            return response()->json([
                'mensaje' => "Estado actualizado a {$validated['estado']} exitosamente.",
                'data' => $solicitud
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Solicitud no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al cambiar estado.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ✅ NUEVO: GET /api/solicitudes/mis-solicitudes
     * Cliente ve su propio historial
     */
    public function misSolicitudes(Request $request): JsonResponse
    {
        try {
            $resultado = $this->solicitudService->obtenerMisSolicitudes($request->user());

            return response()->json([
                'mensaje' => 'Historial de solicitudes obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el historial.',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ✅ NUEVO: GET /api/solicitudes/cliente/{id_cliente}
     * Admin ve historial de un cliente específico
     */
    public function historialCliente(int $id_cliente): JsonResponse
    {
        try {
            $resultado = $this->solicitudService->obtenerHistorialCliente($id_cliente);

            return response()->json([
                'mensaje' => 'Historial del cliente obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Cliente no encontrado.',
                'error' => 'El ID de cliente proporcionado no existe.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el historial.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Validar si la hora preferida del cliente tiene conflicto con un técnico específico
     * GET /api/solicitudes/{uuid}/validar-hora-preferida/{id_tecnico}
     */
    public function validarHoraPreferida(string $uuid, int $id_tecnico): JsonResponse
    {
        try {
            $resultado = $this->solicitudService->validarHoraPreferida($uuid, $id_tecnico);
            return response()->json([
                'mensaje' => 'Validación completada.',
                'data' => $resultado
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Solicitud no encontrada.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al validar.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    /**
     * ✅ NUEVO: GET /api/solicitudes/mi-historial
     * Técnico ve su propio historial de servicios
     */
    public function miHistorial(Request $request): JsonResponse
    {
        try {
            $resultado = $this->solicitudService->obtenerHistorialTecnico($request->user());
            return response()->json([
                'mensaje' => 'Historial de servicios obtenido exitosamente.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el historial.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
        /**
     * ✅ NUEVO: GET /api/solicitudes/tecnico/{id_tecnico}/validar-saturacion
     * Solo Admin: Verificar si un técnico puede recibir más trabajo
     */
    public function validarSaturacionTecnico(int $id_tecnico): JsonResponse
    {
        try {
            $resultado = $this->solicitudService->validarSaturacionTecnico($id_tecnico);
            
            return response()->json([
                'mensaje' => 'Validación de saturación completada.',
                'data' => $resultado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al validar saturación.',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
