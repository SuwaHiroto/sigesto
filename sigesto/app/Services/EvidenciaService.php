<?php

namespace App\Services;

use App\Models\Evidencia;
use App\Models\Solicitud;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Exception;

class EvidenciaService
{
    private CloudinaryService $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * ✅ UNIFICADO: Subir una o múltiples evidencias
     */
    public function subirEvidencia(array $datos, $archivos): array
    {
        if ($archivos instanceof UploadedFile) {
            $evidencia = $this->subirUnaEvidencia($datos, $archivos);
            return [
                'total_subidas' => 1,
                'total_errores' => 0,
                'evidencias' => [[
                    'id_evidencia' => $evidencia->id_evidencia,
                    'tipo_evidencia' => $evidencia->tipo_evidencia,
                    'url_archivo' => $evidencia->url_archivo,
                    'observaciones' => $datos['observaciones'] ?? null,
                ]],
                'errores' => [],
            ];
        }

        if (is_array($archivos)) {
            return $this->subirMultiplesEvidencias($datos, $archivos);
        }

        throw new Exception('Formato de archivos inválido.');
    }

    /**
     * ✅ NUEVO: Validar que el estado de la solicitud permita el tipo de evidencia
     */
    private function validarEstadoParaEvidencia(string $uuidSolicitud, string $tipoEvidencia): void
    {
        $solicitud = Solicitud::where('uuid_solicitud', $uuidSolicitud)->firstOrFail();
        $estado = $solicitud->estado;

        if ($tipoEvidencia === 'ANTES') {
            // ✅ PERMITIMOS 'PAGADA' PARA EL FLUJO DE PAGO 100% INICIAL
            if (!in_array($estado, ['APROBADA', 'EN_PROCESO', 'PAGADA'])) {
                throw new Exception("No se puede subir evidencia 'ANTES' cuando la solicitud está en estado: {$estado}.");
            }
        } elseif (in_array($tipoEvidencia, ['DURANTE', 'DESPUES'])) {
            if (!in_array($estado, ['EN_PROCESO', 'PAGADA', 'FINALIZADA'])) {
                throw new Exception("No se puede subir evidencia '{$tipoEvidencia}' cuando la solicitud está en estado: {$estado}.");
            }
        }
    }

    private function subirUnaEvidencia(array $datos, UploadedFile $archivo): Evidencia
    {
        // 1. Validar estado antes de hacer cualquier cosa
        $this->validarEstadoParaEvidencia($datos['uuid_solicitud'], $datos['tipo_evidencia']);

        // 2. Subir a Cloudinary
        $resultado = $this->cloudinaryService->subir($archivo, 'sigesto/evidencias');

        // 3. Guardar en BD
        return Evidencia::create([
            'uuid_solicitud' => $datos['uuid_solicitud'],
            'tipo_evidencia' => $datos['tipo_evidencia'],
            'url_archivo' => $resultado['url'],
            'observaciones' => $datos['observaciones'] ?? null,
        ]);
    }

    private function subirMultiplesEvidencias(array $datos, array $archivos): array
    {
        $evidenciasCreadas = [];
        $errores = [];

        foreach ($archivos as $index => $archivo) {
            try {
                $tipoEvidencia = $datos['tipos_evidencia'][$index] ?? 'GENERAL';
                
                // 1. Validar estado para CADA archivo individualmente
                $this->validarEstadoParaEvidencia($datos['uuid_solicitud'], $tipoEvidencia);

                // 2. Subir a Cloudinary
                $resultado = $this->cloudinaryService->subir($archivo, 'sigesto/evidencias');

                // 3. Guardar en BD
                $evidencia = Evidencia::create([
                    'uuid_solicitud' => $datos['uuid_solicitud'],
                    'tipo_evidencia' => $tipoEvidencia,
                    'url_archivo' => $resultado['url'],
                    'observaciones' => $datos['observaciones'] ?? null,
                ]);

                $evidenciasCreadas[] = [
                    'id_evidencia' => $evidencia->id_evidencia,
                    'tipo_evidencia' => $evidencia->tipo_evidencia,
                    'url_archivo' => $evidencia->url_archivo,
                    'nombre_archivo' => $archivo->getClientOriginalName(),
                ];
            } catch (Exception $e) {
                $errores[] = [
                    'archivo' => $archivo->getClientOriginalName() ?? "archivo_{$index}",
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_subidas' => count($evidenciasCreadas),
            'total_errores' => count($errores),
            'evidencias' => $evidenciasCreadas,
            'errores' => $errores,
        ];
    }

    public function obtenerEvidenciasPorSolicitud(string $uuid): array
    {
        Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();

        $evidencias = Evidencia::where('uuid_solicitud', $uuid)
            ->orderBy('fecha_subida', 'desc')
            ->get()
            ->map(fn($e) => [
                'id_evidencia' => $e->id_evidencia,
                'uuid_solicitud' => $e->uuid_solicitud,
                'tipo_evidencia' => $e->tipo_evidencia,
                'url_archivo' => $e->url_archivo,
                'observaciones' => $e->observaciones,
                'fecha_subida' => $e->fecha_subida?->format('Y-m-d H:i:s'),
            ]);

        return [
            'uuid_solicitud' => $uuid,
            'total_evidencias' => $evidencias->count(),
            'evidencias' => $evidencias->toArray(),
        ];
    }

    public function eliminarEvidencia(int $id): void
    {
        $evidencia = Evidencia::findOrFail($id);

        if ($evidencia->url_archivo) {
            $this->cloudinaryService->eliminar($evidencia->url_archivo);
        }

        $evidencia->delete();
    }
}