<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Solicitud;
use App\Models\Usuario;
use App\Models\HistorialEstado;
use Illuminate\Support\Facades\DB;
use App\Models\DetalleCotizacion;
use Illuminate\Http\UploadedFile;
use Exception;

class FinanzasService
{
    private CloudinaryService $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * ✅ MODIFICADO: Registra el pago final (con subida opcional a Cloudinary)
     */
    public function registrarPago(
        array $datos,
        int $idUsuarioRegistra,
        ?UploadedFile $archivoComprobante = null
    ): array {
        return DB::transaction(function () use ($datos, $idUsuarioRegistra, $archivoComprobante) {
            $solicitud = Solicitud::where('uuid_solicitud', $datos['uuid_solicitud'])->firstOrFail();

            // 1. Validar estado de la solicitud
            if ($solicitud->estado === 'CANCELADA') {
                throw new Exception('No se puede registrar pago en una solicitud cancelada.');
            }

            if ($solicitud->estado === 'PAGADA') {
                throw new Exception('Esta solicitud ya está completamente pagada.');
            }

            if ($datos['monto_pagado'] <= 0) {
                throw new Exception('El monto del pago debe ser mayor a 0.');
            }

            // 2. Obtener la cotización
            $cotizacion = $solicitud->cotizacion;
            if (!$cotizacion) {
                throw new Exception('La solicitud no tiene cotización asociada.');
            }

            // 3. Calcular total de cotización con respaldo manual
            $totalCotizacion = (float) $cotizacion->total;

            if ($totalCotizacion <= 0) {
                $totalCotizacion = DetalleCotizacion::where('id_cotizacion', $cotizacion->id_cotizacion)
                    ->get()
                    ->sum(function ($detalle) {
                        return $detalle->cantidad * $detalle->precio_aplicado;
                    });
                $totalCotizacion = $totalCotizacion * (1 + ($cotizacion->tasa_igv / 100));
            }

            if ($totalCotizacion <= 0) {
                throw new Exception('La cotización no tiene un total válido. Verifique los items.');
            }

            // 4. Calcular total ya pagado
            $totalPagado = Pago::where('uuid_solicitud', $datos['uuid_solicitud'])
                ->where('estado_pago', 'COMPLETADO')
                ->sum('monto_pagado');

            // 5. Validación crítica: No exceder el total
            $nuevoTotal = (float) $totalPagado + (float) $datos['monto_pagado'];

            if ($nuevoTotal > $totalCotizacion + 0.01) {
                $saldoPendiente = $totalCotizacion - $totalPagado;
                throw new Exception(
                    "El pago excede el monto total. " .
                        "Total cotización: S/ " . number_format($totalCotizacion, 2) . ", " .
                        "Ya pagado: S/ " . number_format($totalPagado, 2) . ", " .
                        "Saldo pendiente: S/ " . number_format($saldoPendiente, 2) . ", " .
                        "Monto enviado: S/ " . number_format($datos['monto_pagado'], 2)
                );
            }

            // 6. ✅ NUEVO: Subir comprobante a Cloudinary si se envió archivo
            $urlComprobante = $datos['url_comprobante'] ?? null;

            if ($archivoComprobante) {
                $resultado = $this->cloudinaryService->subir(
                    $archivoComprobante,
                    'sigesto/comprobantes' // ✅ Carpeta específica para pagos
                );
                $urlComprobante = $resultado['url'];
            }

            // 7. Crear el pago
            $pago = Pago::create([
                'uuid_solicitud' => $datos['uuid_solicitud'],
                'monto_pagado' => $datos['monto_pagado'],
                'metodo_pago' => $datos['metodo_pago'],
                'nro_operacion' => $datos['nro_operacion'] ?? null,
                'url_comprobante' => $urlComprobante,
                'estado_pago' => 'COMPLETADO',
                'tipo_pago' => 'FINAL',
                'id_usuario_registro' => $idUsuarioRegistra,
                'fecha_pago' => now(),
            ]);

            // 8. Verificar si quedó completamente pagada
            $this->verificarPagoCompleto($solicitud, $totalCotizacion, $idUsuarioRegistra);

            return [
                'mensaje' => 'Pago registrado exitosamente.',
                'data' => $pago,
                'total_pagado' => $nuevoTotal,
                'total_cotizacion' => $totalCotizacion,
                'saldo_pendiente' => max(0, $totalCotizacion - $nuevoTotal),
            ];
        });
    }

       /**
     * ✅ MODIFICADO: Cliente registra el adelanto (o pago total) con subida opcional a Cloudinary
     */
    public function registrarAdelanto(
        string $uuid,
        array $data,
        Usuario $usuario,
        ?UploadedFile $archivoComprobante = null
    ): array {
        return DB::transaction(function () use ($uuid, $data, $usuario, $archivoComprobante) {
            $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();

            // 1. ✅ CORREGIDO: Validar que la solicitud permita pagos (no esté cancelada o ya pagada)
            if (in_array($solicitud->estado, ['PAGADA', 'CANCELADA'])) {
                throw new Exception('No se puede registrar un pago en una solicitud con estado: ' . $solicitud->estado);
            }

            // 2. Validar cotización
            $cotizacion = $solicitud->cotizacion;
            if (!$cotizacion) {
                throw new Exception('La solicitud no tiene cotización asociada.');
            }

            $totalCotizacion = (float) $cotizacion->total;

            // 3. ✅ NUEVO: Calcular lo que ya se ha pagado y aprobado oficialmente
            $totalPagado = Pago::where('uuid_solicitud', $uuid)
                ->where('estado_pago', 'COMPLETADO')
                ->sum('monto_pagado');

            $saldoPendiente = max(0, $totalCotizacion - $totalPagado);

            if ($saldoPendiente <= 0) {
                throw new Exception('Esta solicitud ya está completamente pagada.');
            }

            // 4. Validar monto positivo y contra el saldo pendiente real
            $montoAPagar = (float) $data['monto_pagado'];
            
            if ($montoAPagar <= 0) {
                throw new Exception('El monto del pago debe ser mayor a 0.');
            }

            // Permitimos un margen de 0.01 por precisión de decimales en PHP
            if ($montoAPagar > $saldoPendiente + 0.01) {
                throw new Exception(
                    "El pago excede el saldo pendiente. Saldo real: S/ " . number_format($saldoPendiente, 2) . 
                    ", Monto enviado: S/ " . number_format($montoAPagar, 2)
                );
            }

            // 5. ✅ LÓGICA INTELIGENTE: Determinar si este pago cubre el saldo restante
            $esPagoTotal = $montoAPagar >= ($saldoPendiente - 0.01);
            $tipoPago = $esPagoTotal ? 'FINAL' : 'ADELANTO';

            // 6. Subir comprobante a Cloudinary si se envió archivo
            $urlComprobante = $data['url_comprobante'] ?? null;
            if ($archivoComprobante) {
                $resultado = $this->cloudinaryService->subir(
                    $archivoComprobante,
                    'sigesto/comprobantes'
                );
                $urlComprobante = $resultado['url'];
            }

            // 7. Crear el registro del pago
            $pago = Pago::create([
                'uuid_solicitud' => $uuid,
                'monto_pagado' => $montoAPagar,
                'metodo_pago' => $data['metodo_pago'],
                'nro_operacion' => $data['nro_operacion'] ?? null,
                'url_comprobante' => $urlComprobante,
                'estado_pago' => 'PENDIENTE_APROBACION',
                'tipo_pago' => $tipoPago, 
                'id_usuario_registro' => $usuario->id_usuario,
                'fecha_pago' => now(),
            ]);

            // 8. Cambiar estado de la solicitud a REVISION_PAGO
            $estadoAnterior = $solicitud->estado;
            $solicitud->update(['estado' => 'REVISION_PAGO']);

            HistorialEstado::create([
                'uuid_solicitud' => $uuid,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'REVISION_PAGO',
                'id_usuario_accion' => $usuario->id_usuario,
            ]);

            return [
                'mensaje' => $esPagoTotal 
                    ? 'Pago final registrado exitosamente. Pasará a revisión.' 
                    : 'Adelanto registrado exitosamente. Pasará a revisión de pago.',
                'pago' => $pago,
                'nuevo_estado_solicitud' => 'REVISION_PAGO',
                'tipo_pago_registrado' => $tipoPago,
                'saldo_pendiente_anterior' => round($saldoPendiente, 2),
                'porcentaje_pagado_total' => round((($totalPagado + $montoAPagar) / $totalCotizacion) * 100, 2) . '%',
                'es_liquidacion_total' => $esPagoTotal,
            ];
        });
    }

    /**
     * ✅ Obtener historial de pagos de una solicitud
     */
    public function obtenerPagosPorSolicitud(string $uuid): array
    {
        Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();

        $pagos = Pago::where('uuid_solicitud', $uuid)
            ->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($pago) {
                return [
                    'id_pago' => $pago->id_pago,
                    'uuid_solicitud' => $pago->uuid_solicitud,
                    'monto_pagado' => $pago->monto_pagado,
                    'metodo_pago' => $pago->metodo_pago,
                    'nro_operacion' => $pago->nro_operacion,
                    'url_comprobante' => $pago->url_comprobante,
                    'estado_pago' => $pago->estado_pago,
                    'tipo_pago' => $pago->tipo_pago,
                    'fecha_pago' => $pago->fecha_pago?->format('Y-m-d H:i:s'),
                ];
            });

        return [
            'uuid_solicitud' => $uuid,
            'total_pagado' => $pagos->sum('monto_pagado'),
            'pagos' => $pagos->toArray(),
        ];
    }

    /**
     * ✅ Listar todos los pagos con filtrado en SQL
     */
    /**
 * Listar todos los pagos (Solo Admin)
 */
public function listarPagosGenerales(?string $fechaInicio = null, ?string $fechaFin = null): array
{
    $query = Pago::with([
        'solicitud.cliente.usuario',  // ✅ Cargar relación con cliente
        'solicitud.tecnico.usuario'   // ✅ Opcional: también el técnico
    ]);

    // Filtro de fechas opcional
    if ($fechaInicio && $fechaFin) {
        $query->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
    }

    $pagos = $query->orderBy('fecha_pago', 'desc')->get()->map(function ($pago) {
        return [
            'id_pago' => $pago->id_pago,
            'uuid_solicitud' => $pago->uuid_solicitud,
            'monto_pagado' => round($pago->monto_pagado, 2),
            'metodo_pago' => $pago->metodo_pago,
            'tipo_pago' => $pago->tipo_pago,
            'nro_operacion' => $pago->nro_operacion,
            'url_comprobante' => $pago->url_comprobante,
            'estado_pago' => $pago->estado_pago,
            'fecha_pago' => $pago->fecha_pago?->format('Y-m-d H:i:s'),
            
            // ✅ NUEVO: Datos del cliente
            'cliente' => $pago->solicitud?->cliente?->usuario 
                ? trim($pago->solicitud->cliente->usuario->nombres . ' ' . $pago->solicitud->cliente->usuario->apellidos) 
                : 'N/A',
            
            // ✅ NUEVO: Datos del técnico (opcional pero útil)
            'tecnico' => $pago->solicitud?->tecnico?->usuario 
                ? trim($pago->solicitud->tecnico->usuario->nombres . ' ' . $pago->solicitud->tecnico->usuario->apellidos) 
                : 'Sin asignar',
            
            // ✅ NUEVO: Estado de la solicitud
            'estado_solicitud' => $pago->solicitud?->estado ?? 'N/A',
        ];
    });

    return [
        'total_pagos' => $pagos->count(),
        'monto_total' => round($pagos->sum('monto_pagado'), 2),
        'pagos' => $pagos->values()->toArray(),
    ];
}

    /**
     * ✅ Obtener datos de la cuenta bancaria
     */
    public function obtenerCuentaBancaria(): array
    {
        return [
            'banco' => 'Banco de Crédito del Perú (BCP)',
            'tipo_cuenta' => 'Ahorros',
            'numero_cuenta' => '191-12345678-0-01',
            'cci' => '00219100123456780101',
            'titular' => 'SIGESTO S.A.C.',
            'moneda' => 'Soles (PEN)',
            'mensaje' => 'Realice el adelanto y registre el número de operación en el sistema.'
        ];
    }

    /**
     * ✅ Admin verifica el pago y aprueba la solicitud
     */
public function verificarPago(int $idPago, Usuario $admin): array
{
    return DB::transaction(function () use ($idPago, $admin) {
        $pago = Pago::findOrFail($idPago);

        if ($pago->estado_pago !== 'PENDIENTE_APROBACION') {
            throw new Exception('Este pago ya fue procesado.');
        }

        $pago->update([
            'estado_pago' => 'COMPLETADO',
            'id_usuario_aprobacion' => $admin->id_usuario,
            'fecha_aprobacion' => now()
        ]);

        $solicitud = Solicitud::where('uuid_solicitud', $pago->uuid_solicitud)->firstOrFail();
        $cotizacion = $solicitud->cotizacion;
        $estadoAnterior = $solicitud->estado;

        $totalPagado = Pago::where('uuid_solicitud', $solicitud->uuid_solicitud)
            ->where('estado_pago', 'COMPLETADO')
            ->sum('monto_pagado');

        $esPagoTotal = $totalPagado >= $cotizacion->total;
        
        // ✅ CORRECCIÓN: Si es pago total → PAGADA, si es adelanto → APROBADA (NO EN_PROCESO)
        $nuevoEstado = $esPagoTotal ? 'PAGADA' : 'APROBADA';

        $solicitud->update(['estado' => $nuevoEstado]);

        $cotizacion->update([
            'estado' => $esPagoTotal ? 'LIQUIDADA' : 'APROBADA'
        ]);

        HistorialEstado::create([
            'uuid_solicitud' => $solicitud->uuid_solicitud,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $nuevoEstado,
            'id_usuario_accion' => $admin->id_usuario,
        ]);

        return [
            'mensaje' => $esPagoTotal 
                ? 'Pago final verificado. La solicitud está PAGADA.' 
                : 'Adelanto verificado. El técnico puede iniciar el trabajo.',
            'nuevo_estado' => $nuevoEstado,
        ];
    });
}
    /**
     * ✅ Verificar si la solicitud está completamente pagada
     */
    private function verificarPagoCompleto(Solicitud $solicitud, float $totalCotizacion, int $idUsuario): void
    {
        $totalPagado = Pago::where('uuid_solicitud', $solicitud->uuid_solicitud)
            ->where('estado_pago', 'COMPLETADO')
            ->sum('monto_pagado');

        if ($totalPagado >= $totalCotizacion && $totalCotizacion > 0) {
            $solicitud = $solicitud->fresh();

            if (!in_array($solicitud->estado, ['CANCELADA', 'PAGADA'])) {
                $estadoAnterior = $solicitud->estado;
                $solicitud->update(['estado' => 'PAGADA']);

                HistorialEstado::create([
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'PAGADA',
                    'id_usuario_accion' => $idUsuario,
                ]);
            }
        }
    }

    /**
     * ✅ Obtener resumen de pagos de una solicitud (saldo pendiente)
     */
    public function obtenerResumenPagos(string $uuid): array
    {
        $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();
        $cotizacion = $solicitud->cotizacion;

        $totalCotizacion = $cotizacion ? (float) $cotizacion->total : 0;

        if ($totalCotizacion <= 0 && $cotizacion) {
            $totalCotizacion = DetalleCotizacion::where('id_cotizacion', $cotizacion->id_cotizacion)
                ->get()
                ->sum(fn($d) => $d->cantidad * $d->precio_aplicado);
            $totalCotizacion = $totalCotizacion * (1 + ($cotizacion->tasa_igv / 100));
        }

        $totalPagado = Pago::where('uuid_solicitud', $uuid)
            ->where('estado_pago', 'COMPLETADO')
            ->sum('monto_pagado');

        $adelanto = Pago::where('uuid_solicitud', $uuid)
            ->where('tipo_pago', 'ADELANTO')
            ->where('estado_pago', 'COMPLETADO')
            ->first();

        return [
            'uuid_solicitud' => $uuid,
            'estado_solicitud' => $solicitud->estado,
            'total_cotizacion' => round($totalCotizacion, 2),
            'total_pagado' => round($totalPagado, 2),
            'saldo_pendiente' => round(max(0, $totalCotizacion - $totalPagado), 2),
            'tiene_adelanto' => $adelanto !== null,
            'monto_adelanto' => $adelanto ? round((float) $adelanto->monto_pagado, 2) : 0,
            'metodo_adelanto' => $adelanto ? $adelanto->metodo_pago : null,
            'es_pagada' => $totalPagado >= $totalCotizacion && $totalCotizacion > 0,
        ];
    }

    /**
     * ✅ Obtener historial de pagos del cliente autenticado
     */
    public function obtenerMisPagos(Usuario $usuario, ?string $estado = null): array
    {
        $perfilCliente = $usuario->perfilCliente;
        if (!$perfilCliente) {
            throw new Exception('El usuario no tiene perfil de cliente.');
        }

        $query = Pago::with(['solicitud' => function ($q) {
            $q->select('uuid_solicitud', 'estado', 'descripcion_problema', 'direccion_servicio');
        }])
            ->whereHas('solicitud', function ($q) use ($perfilCliente) {
                $q->where('id_cliente', $perfilCliente->id_cliente);
            })
            ->orderBy('fecha_pago', 'desc');

        if ($estado) {
            $query->where('estado_pago', $estado);
        }

        $pagos = $query->get()->map(function ($pago) {
            return [
                'id_pago' => $pago->id_pago,
                'uuid_solicitud' => $pago->uuid_solicitud,
                'monto_pagado' => $pago->monto_pagado,
                'metodo_pago' => $pago->metodo_pago,
                'nro_operacion' => $pago->nro_operacion,
                'url_comprobante' => $pago->url_comprobante,
                'estado_pago' => $pago->estado_pago,
                'tipo_pago' => $pago->tipo_pago,
                'fecha_pago' => $pago->fecha_pago?->format('Y-m-d H:i:s'),
                'solicitud_info' => $pago->solicitud ? [
                    'estado_actual' => $pago->solicitud->estado,
                    'descripcion_problema' => $pago->solicitud->descripcion_problema,
                    'direccion_servicio' => $pago->solicitud->direccion_servicio,
                ] : null,
            ];
        });

        return [
            'total_pagos' => $pagos->count(),
            'total_pagado' => $pagos->where('estado_pago', 'COMPLETADO')->sum('monto_pagado'),
            'pagos_completados' => $pagos->where('estado_pago', 'COMPLETADO')->count(),
            'pagos_pendientes' => $pagos->where('estado_pago', 'PENDIENTE_APROBACION')->count(),
            'filtro_aplicado' => $estado ?? 'todos',
            'pagos' => $pagos->toArray(),
        ];
    }
    
    /**
 * ✅ NUEVO: Admin rechaza un pago pendiente (sin motivo, solo cambio de estado)
 * PATCH /api/finanzas/{id_pago}/rechazar
 */
public function rechazarPago(int $idPago, Usuario $admin): array
{
    return DB::transaction(function () use ($idPago, $admin) {
        $pago = Pago::findOrFail($idPago);
        
        if ($pago->estado_pago !== 'PENDIENTE_APROBACION') {
            throw new Exception('Solo se pueden rechazar pagos en estado PENDIENTE_APROBACION.');
        }

        $solicitud = Solicitud::where('uuid_solicitud', $pago->uuid_solicitud)->firstOrFail();
        $estadoAnteriorSolicitud = $solicitud->estado;

        // 1. Cambiar estado del pago a RECHAZADO
        $pago->update([
            'estado_pago' => 'RECHAZADO',
            'id_usuario_aprobacion' => $admin->id_usuario,
            'fecha_aprobacion' => now(),
        ]);

        // 2. Revertir el estado de la solicitud inteligentemente
        $totalPagado = Pago::where('uuid_solicitud', $solicitud->uuid_solicitud)
            ->where('estado_pago', 'COMPLETADO')
            ->sum('monto_pagado');

        // Si no hay pagos completados, vuelve a COTIZADA (para que suba otro comprobante)
        // Si ya hay un adelanto completado, vuelve a APROBADA (para que suba el saldo)
        $nuevoEstadoSolicitud = ($totalPagado == 0) ? 'COTIZADA' : 'APROBADA';
        
        $solicitud->update(['estado' => $nuevoEstadoSolicitud]);

        // 3. Registrar en el historial
        HistorialEstado::create([
            'uuid_solicitud' => $solicitud->uuid_solicitud,
            'estado_anterior' => $estadoAnteriorSolicitud,
            'estado_nuevo' => $nuevoEstadoSolicitud,
            'id_usuario_accion' => $admin->id_usuario,
        ]);

        return [
            'mensaje' => 'Pago rechazado exitosamente.',
            'nuevo_estado_solicitud' => $nuevoEstadoSolicitud,
        ];
    });
}
}
