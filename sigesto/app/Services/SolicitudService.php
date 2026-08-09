<?php

namespace App\Services;

use App\Models\Solicitud;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\HistorialEstado;
use App\Models\Usuario;
use App\Models\Pago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Collection;

class SolicitudService
{
    /**
     * Crea la solicitud, la cotización en borrador y el historial inicial.
     */
    public function crearSolicitud(array $datos, int $idUsuarioActual): Solicitud
    {
        DB::beginTransaction();
        try {
            // 1. Crear Solicitud (incluyendo urgencia, preferencias y coordenadas)
            $solicitud = Solicitud::create([
                'id_cliente' => $datos['id_cliente'],
                'estado' => 'PENDIENTE',
                'descripcion_problema' => $datos['descripcion_problema'],
                'direccion_servicio' => $datos['direccion_servicio'],
                'latitud' => $datos['latitud'] ?? null,       // ✅ NUEVO
                'longitud' => $datos['longitud'] ?? null,     // ✅ NUEVO
                'es_urgente' => $datos['es_urgente'] ?? false,
                'fecha_preferida' => $datos['fecha_preferida'] ?? null,
                'hora_preferida' => $datos['hora_preferida'] ?? null,
                'notas_disponibilidad' => $datos['notas_disponibilidad'] ?? null,
                'materiales_cliente' => $datos['materiales_cliente'] ?? null,
            ]);

            // 2. Crear Cotización inicial en BORRADOR
            Cotizacion::create([
                'uuid_solicitud' => $solicitud->uuid_solicitud,
                'estado' => 'BORRADOR',
                'tasa_igv' => 18.00,
                'id_usuario_creador' => $idUsuarioActual,
            ]);

            // 3. Registrar en el historial de estados
            HistorialEstado::create([
                'uuid_solicitud' => $solicitud->uuid_solicitud,
                'estado_anterior' => null,
                'estado_nuevo' => 'PENDIENTE',
                'id_usuario_accion' => $idUsuarioActual,
            ]);

            DB::commit();
            return $solicitud->load('cotizacion');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Asignar Técnico (Cambia estado a ASIGNADA)
     */
    public function asignarTecnico(
        string $uuid,
        int $idTecnico,
        int $idUsuarioAdmin,
        ?string $fechaCoordinada = null,
        ?string $horaCoordinada = null
    ): Solicitud {
        DB::beginTransaction();
        try {
            $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();
            
            // ✅ CORRECCIÓN: Guardar el estado real antes de modificarlo
            $estadoAnterior = $solicitud->estado;

            if ($fechaCoordinada && $horaCoordinada) {
                $this->validarConflictoCoordinacion($idTecnico, $fechaCoordinada, $horaCoordinada, null);
            }

            $solicitud->update([
                'id_tecnico' => $idTecnico,
                'estado' => 'ASIGNADA',
                'fecha_coordinada' => $fechaCoordinada,
                'hora_coordinada' => $horaCoordinada,
            ]);

            // ✅ CORRECCIÓN: Usar la variable $estadoAnterior en lugar de 'PENDIENTE' fijo
            $this->registrarHistorial($solicitud, $estadoAnterior, 'ASIGNADA', $idUsuarioAdmin);

            DB::commit();
            return $solicitud->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Método unificado para TODOS los cambios de estado
     */
    public function cambiarEstado(string $uuid, string $nuevoEstado, Usuario $usuario, ?string $motivo = null): Solicitud
    {
        DB::beginTransaction();
        try {
            $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();
            $estadoAnterior = $solicitud->estado;

            $this->validarPermisoCambioEstado($usuario, $nuevoEstado);
            $this->validarTransicionEstado($estadoAnterior, $nuevoEstado);
            $this->ejecutarLogicaEstado($solicitud, $nuevoEstado, $usuario->id_usuario);

            DB::commit();
            return $solicitud->fresh();
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validar permisos para cambiar a un estado específico
     */
    private function validarPermisoCambioEstado(Usuario $usuario, string $nuevoEstado): void
    {
        $rol = $usuario->rol->nombre;

        $permisos = [
            'ASIGNADA' => ['ADMINISTRADOR'],
            'COTIZADA' => ['TECNICO', 'ADMINISTRADOR'],
            'REVISION_PAGO' => ['CLIENTE'],
            'APROBADA' => ['ADMINISTRADOR'],
            'RECHAZADA' => ['CLIENTE'],
            'EN_PROCESO' => ['TECNICO', 'ADMINISTRADOR'],
            'FINALIZADA' => ['TECNICO', 'ADMINISTRADOR'],
            'PAGADA' => ['ADMINISTRADOR'],
            'CANCELADA' => ['CLIENTE', 'ADMINISTRADOR'],
        ];

        $rolesPermitidos = $permisos[$nuevoEstado] ?? [];

        if (!in_array($rol, $rolesPermitidos)) {
            throw new \Exception(
                "El rol {$rol} no tiene permisos para cambiar el estado a {$nuevoEstado}."
            );
        }
    }

    /**
     * Validar que la transición de estados sea válida
     */
private function validarTransicionEstado(string $estadoActual, string $nuevoEstado): void
{
    $transicionesValidas = [
        'PENDIENTE'     => ['ASIGNADA', 'CANCELADA'],
        'ASIGNADA'      => ['COTIZADA', 'CANCELADA'],
        'COTIZADA'      => ['REVISION_PAGO', 'RECHAZADA', 'CANCELADA'],
        'REVISION_PAGO' => ['APROBADA', 'PAGADA', 'CANCELADA'],
        'APROBADA'      => ['EN_PROCESO', 'CANCELADA'], // ✅ El técnico inicia desde aquí
        'RECHAZADA'     => ['COTIZADA'],
        'EN_PROCESO'    => ['REVISION_PAGO'],
        'PAGADA'        => ['FINALIZADA'],
        'FINALIZADA'    => [],
        'CANCELADA'     => [],
    ];

    $transicionesPermitidas = $transicionesValidas[$estadoActual] ?? [];

    if (!in_array($nuevoEstado, $transicionesPermitidas)) {
        throw new \Exception(
            "Transición inválida. No se puede cambiar de {$estadoActual} a {$nuevoEstado}."
        );
    }
}
    
    /**
     * Ejecutar lógica específica según el nuevo estado
     */
    private function ejecutarLogicaEstado(Solicitud $solicitud, string $nuevoEstado, int $idUsuario): void
    {
        $estadoAnterior = $solicitud->estado;

        // 1. EL GUARDIÁN: Validar que la transición esté permitida por la matriz
        $this->validarTransicionEstado($estadoAnterior, $nuevoEstado);

        $cotizacion = $solicitud->cotizacion;

        // 2. Lógica específica según el estado de destino
        if ($nuevoEstado === 'RECHAZADA') {
            if (!$cotizacion || $cotizacion->estado !== 'ENVIADA') {
                throw new \Exception('No hay una cotización enviada para rechazar.');
            }
            $cotizacion->update(['estado' => 'BORRADOR']);
        }

        if ($nuevoEstado === 'APROBADA' || $nuevoEstado === 'PAGADA') {
            if (!$cotizacion) {
                throw new \Exception('No hay una cotización asociada a esta solicitud.');
            }
            // Si pasa a PAGADA, la cotización se liquida. Si es APROBADA, se aprueba.
            $nuevoEstadoCotizacion = ($nuevoEstado === 'PAGADA') ? 'LIQUIDADA' : 'APROBADA';
            $cotizacion->update(['estado' => $nuevoEstadoCotizacion]);
        }

        if ($nuevoEstado === 'FINALIZADA') {
            // ✅ LIMPIEZA: Ya no calculamos deudas aquí.
            // La matriz de estados garantiza que solo se llega aquí si el estado es PAGADA.
            if (!$cotizacion) {
                throw new \Exception('No se puede finalizar: la solicitud no tiene cotización.');
            }
            // Aquí podrías agregar: $solicitud->update(['fecha_finalizacion' => now()]); si tienes ese campo.
        }

        if ($nuevoEstado === 'CANCELADA') {
            if ($solicitud->pagos()->where('estado_pago', 'COMPLETADO')->exists()) {
                throw new \Exception('No se puede cancelar una solicitud que ya tiene pagos completados.');
            }
        }

        // 3. Ejecutar el cambio y registrar en el historial
        $solicitud->update(['estado' => $nuevoEstado]);
        $this->registrarHistorial($solicitud, $estadoAnterior, $nuevoEstado, $idUsuario);
    }
    /**
     * Listar todas las solicitudes (Solo Admin)
     * ✅ NUEVO: Urgentes primero, luego por fecha, con coordenadas y cotización
     */
    public function listarSolicitudes(): Collection
    {
        return Solicitud::with(['cliente.usuario', 'tecnico.usuario', 'cotizacion'])
            ->orderBy('es_urgente', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado' => $solicitud->estado,
                    'es_urgente' => $solicitud->es_urgente,
                    'descripcion_problema' => $solicitud->descripcion_problema,
                    'direccion_servicio' => $solicitud->direccion_servicio,
                    'latitud' => $solicitud->latitud ? (float) $solicitud->latitud : null,
                    'longitud' => $solicitud->longitud ? (float) $solicitud->longitud : null,
                    'fecha_creacion' => $solicitud->created_at?->format('Y-m-d H:i:s'),
                    'cliente' => $solicitud->cliente?->usuario ?
                        trim($solicitud->cliente->usuario->nombres . ' ' . $solicitud->cliente->usuario->apellidos) : null,
                    'tecnico' => $solicitud->tecnico?->usuario ?
                        trim($solicitud->tecnico->usuario->nombres . ' ' . $solicitud->tecnico->usuario->apellidos) : null,
                    'cotizacion' => $solicitud->cotizacion ? [
                        'total' => $solicitud->cotizacion->total
                    ] : null,
                ];
            });
    }

    /**
     * Obtiene el detalle completo de una solicitud por su UUID.
     */
    public function obtenerDetalle(string $uuid): array
    {
            $solicitud = Solicitud::with([
        'cliente.usuario',  // ✅ Esto fallará si no hay cliente
        'tecnico.usuario',
        'historial.usuarioAccion',
        'cotizacion.detalles.itemCatalogo',
        'evidencias',
        'pagos'
    ])->where('uuid_solicitud', $uuid)->firstOrFail();

    // ✅ AGREGAR ESTA VALIDACIÓN
    if (!$solicitud->cliente) {
        throw new Exception('Esta solicitud no tiene un cliente válido asociado.');
    }

        $timeline = $solicitud->historial
            ->sortBy('created_at')
            ->map(function ($h) {
                return [
                    'estado_anterior' => $h->estado_anterior,
                    'estado_nuevo' => $h->estado_nuevo,
                    'fecha_cambio' => $h->created_at?->format('Y-m-d H:i:s'),
                    'usuario_accion' => $h->usuarioAccion
                        ? trim($h->usuarioAccion->nombres . ' ' . $h->usuarioAccion->apellidos)
                        : 'Sistema',
                    'rol_usuario' => $h->usuarioAccion?->rol?->nombre ?? null,
                ];
            })
            ->values();

        return [
            'solicitud' => $solicitud->toArray(),
            'timeline' => $timeline
        ];
    }

    /**
     * Eliminar solicitud con validación de pagos
     */
    public function eliminarSolicitud(string $uuid): void
    {
        $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();

        if ($solicitud->pagos()->exists()) {
            throw new Exception('No se puede dar de baja una solicitud que ya tiene pagos registrados.');
        }

        $solicitud->delete();
    }

    /**
     * Hoja de Ruta del Técnico
     * ✅ NUEVO: Urgentes primero, con coordenadas
     */
    public function obtenerHojaRuta(Usuario $usuario): Collection
    {
        $tecnico = $usuario->perfilTecnico;
        if (!$tecnico) {
            throw new Exception('El usuario no tiene perfil de técnico.');
        }

        return Solicitud::with(['cotizacion', 'cliente.usuario'])
            ->where('id_tecnico', $tecnico->id_tecnico)
            ->whereIn('estado', ['ASIGNADA', 'APROBADA', 'EN_PROCESO', 'PAGADA'])
            ->orderBy('es_urgente', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado' => $solicitud->estado,
                    'es_urgente' => $solicitud->es_urgente,
                    'descripcion_problema' => $solicitud->descripcion_problema,
                    'direccion_servicio' => $solicitud->direccion_servicio,
                    'latitud' => $solicitud->latitud ? (float) $solicitud->latitud : null,
                    'longitud' => $solicitud->longitud ? (float) $solicitud->longitud : null,
                    'cotizacion' => $solicitud->cotizacion ? [
                        'total' => $solicitud->cotizacion->total,
                    ] : null,
                ];
            });
    }

    /**
     * Validar y actualizar coordinación
     */
    private function validarYActualizarCoordinacion(Solicitud $solicitud, array $data, Usuario $usuario): void
    {
        if (!$solicitud->id_tecnico) {
            throw new Exception('La solicitud debe tener un técnico asignado antes de coordinar.');
        }

        if (isset($data['fecha_coordinada']) && isset($data['hora_coordinada'])) {
            $this->validarConflictoCoordinacion(
                $solicitud->id_tecnico,
                $data['fecha_coordinada'],
                $data['hora_coordinada'],
                $solicitud->uuid_solicitud
            );
        }

        $updateData = [];
        if (isset($data['fecha_coordinada'])) $updateData['fecha_coordinada'] = $data['fecha_coordinada'];
        if (isset($data['hora_coordinada'])) $updateData['hora_coordinada'] = $data['hora_coordinada'];
        if (isset($data['notas_coordinacion'])) $updateData['notas_coordinacion'] = $data['notas_coordinacion'];

        if (!empty($updateData)) {
            $solicitud->update($updateData);
        }
    }

    /**
     * Actualizar items + coordinación + datos generales
     */
    public function actualizarSolicitud(string $uuid, array $data, Usuario $usuario): Solicitud
    {
        DB::beginTransaction();
        try {
            $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();

            $this->validarPermisoEdicion($usuario, $solicitud);

            // Si vienen items, asignarItems ya maneja todo (cotización + estado)
            if (isset($data['items']) && !empty($data['items'])) {
                $this->asignarItems($uuid, $data['items'], $usuario->id_usuario);
            }

            if (isset($data['fecha_coordinada']) || isset($data['hora_coordinada']) || isset($data['notas_coordinacion'])) {
                $this->validarYActualizarCoordinacion($solicitud, $data, $usuario);
            }

            $updateData = [];
            if (isset($data['descripcion_problema'])) $updateData['descripcion_problema'] = $data['descripcion_problema'];
            if (isset($data['direccion_servicio'])) $updateData['direccion_servicio'] = $data['direccion_servicio'];

            if (!empty($updateData)) {
                $solicitud->update($updateData);
            }

            DB::commit();
            return $solicitud->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar solicitud: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validar conflictos de coordinación
     */
    private function validarConflictoCoordinacion(
        int $idTecnico,
        string $fechaCoordinada,
        string $horaCoordinada,
        ?string $uuidSolicitudExcluir = null
    ): void {
        $resultado = DB::select(
            'CALL sp_verificar_conflicto_coordinacion(?, ?, ?, ?)',
            [
                $idTecnico,
                $fechaCoordinada,
                $horaCoordinada,
                $uuidSolicitudExcluir
            ]
        );

        if ($resultado[0]->tiene_conflicto == 1) {
            throw new Exception($resultado[0]->mensaje_conflicto);
        }
    }

    /**
     * Validar que el usuario pueda editar la solicitud
     */
    private function validarPermisoEdicion(Usuario $usuario, Solicitud $solicitud): void
    {
        $rol = $usuario->rol->nombre;

        if (!in_array($rol, ['TECNICO', 'ADMINISTRADOR'])) {
            throw new Exception("El rol {$rol} no tiene permisos para editar esta solicitud.");
        }

        if ($rol === 'TECNICO' && $solicitud->id_tecnico !== $usuario->perfilTecnico->id_tecnico) {
            throw new Exception("No puedes editar una solicitud que no está asignada a ti.");
        }
    }

    /**
     * Helper privado para auditoría
     */
    private function registrarHistorial(Solicitud $solicitud, string $anterior, string $nuevo, int $idUsuario): void
    {
        HistorialEstado::create([
            'uuid_solicitud' => $solicitud->uuid_solicitud,
            'estado_anterior' => $anterior,
            'estado_nuevo' => $nuevo,
            'id_usuario_accion' => $idUsuario,
        ]);
    }

    /**
     * ✅ NUEVO: Obtener historial de solicitudes del cliente autenticado
     */
    public function obtenerMisSolicitudes(Usuario $usuario): array
    {
        $perfilCliente = $usuario->perfilCliente;
        if (!$perfilCliente) {
            throw new Exception('El usuario no tiene perfil de cliente.');
        }

        $solicitudes = Solicitud::with([
            'tecnico.usuario:id_usuario,nombres,apellidos',
            'cotizacion:id_cotizacion,uuid_solicitud,estado,subtotal,igv,total',
            'pagos:id_pago,uuid_solicitud,monto_pagado,estado_pago,tipo_pago,fecha_pago'
        ])
            ->where('id_cliente', $perfilCliente->id_cliente)
            ->orderBy('es_urgente', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado' => $solicitud->estado,
                    'es_urgente' => $solicitud->es_urgente,
                    'descripcion_problema' => $solicitud->descripcion_problema,
                    'direccion_servicio' => $solicitud->direccion_servicio,
                    'latitud' => $solicitud->latitud ? (float) $solicitud->latitud : null,
                    'longitud' => $solicitud->longitud ? (float) $solicitud->longitud : null,
                    'materiales_cliente' => $solicitud->materiales_cliente,
                    'fecha_creacion' => $solicitud->created_at?->format('Y-m-d H:i:s'),
                    'fecha_coordinada' => $solicitud->fecha_coordinada,
                    'hora_coordinada' => $solicitud->hora_coordinada,
                    'tecnico' => $solicitud->tecnico ? [
                        'nombre_completo' => trim(
                            $solicitud->tecnico->usuario->nombres . ' ' .
                                $solicitud->tecnico->usuario->apellidos
                        ),
                        'especialidad' => $solicitud->tecnico->especialidad ?? null,
                    ] : null,
                    'cotizacion' => $solicitud->cotizacion ? [
                        'estado' => $solicitud->cotizacion->estado,
                        'total' => $solicitud->cotizacion->total,
                    ] : null,
                    'total_pagado' => $solicitud->pagos
                        ->where('estado_pago', 'COMPLETADO')
                        ->sum('monto_pagado'),
                ];
            });

        return [
            'total_solicitudes' => $solicitudes->count(),
            'solicitudes_urgentes' => $solicitudes->where('es_urgente', true)->count(),
            'solicitudes_activas' => $solicitudes->whereIn('estado', ['PENDIENTE', 'ASIGNADA', 'COTIZADA', 'REVISION_PAGO', 'APROBADA', 'EN_PROCESO'])->count(),
            'solicitudes_finalizadas' => $solicitudes->whereIn('estado', ['FINALIZADA', 'PAGADA'])->count(),
            'solicitudes' => $solicitudes->toArray(),
        ];
    }

    /**
     * ✅ CORREGIDO: Obtener historial de un cliente específico (Admin)
     */
    public function obtenerHistorialCliente(int $idCliente): array
    {
        $perfilCliente = \App\Models\PerfilCliente::with('usuario')
            ->findOrFail($idCliente);

        $solicitudes = Solicitud::with([
            'tecnico.usuario:id_usuario,nombres,apellidos',
            'cotizacion:id_cotizacion,uuid_solicitud,estado,subtotal,igv,total',
            'pagos:id_pago,uuid_solicitud,monto_pagado,estado_pago,tipo_pago,fecha_pago'
        ])
            ->where('id_cliente', $idCliente)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado' => $solicitud->estado,
                    'descripcion_problema' => $solicitud->descripcion_problema,
                    'direccion_servicio' => $solicitud->direccion_servicio,
                    'latitud' => $solicitud->latitud ? (float) $solicitud->latitud : null,
                    'longitud' => $solicitud->longitud ? (float) $solicitud->longitud : null,
                    'fecha_creacion' => $solicitud->created_at?->format('Y-m-d H:i:s'),
                    'fecha_coordinada' => $solicitud->fecha_coordinada,
                    'hora_coordinada' => $solicitud->hora_coordinada,
                    'tecnico' => $solicitud->tecnico && $solicitud->tecnico->usuario ? [
                        'nombre_completo' => trim(
                            $solicitud->tecnico->usuario->nombres . ' ' .
                                $solicitud->tecnico->usuario->apellidos
                        ),
                    ] : null,
                    'cotizacion' => $solicitud->cotizacion ? [
                        'estado' => $solicitud->cotizacion->estado,
                        'total' => $solicitud->cotizacion->total,
                    ] : null,
                    'total_pagado' => $solicitud->pagos
                        ->where('estado_pago', 'COMPLETADO')
                        ->sum('monto_pagado'),
                ];
            });

        return [
            'cliente' => [
                'id_cliente' => $perfilCliente->id_cliente,
                'nombre_completo' => trim($perfilCliente->usuario->nombres . ' ' . $perfilCliente->usuario->apellidos),
                'email' => $perfilCliente->usuario->email,
                'telefono' => $perfilCliente->telefono,
            ],
            'total_solicitudes' => $solicitudes->count(),
            'total_gastado' => $solicitudes->sum('total_pagado'),
            'solicitudes_activas' => $solicitudes->whereIn('estado', ['PENDIENTE', 'ASIGNADA', 'COTIZADA', 'REVISION_PAGO', 'APROBADA', 'EN_PROCESO'])->count(),
            'solicitudes_finalizadas' => $solicitudes->whereIn('estado', ['FINALIZADA', 'PAGADA'])->count(),
            'solicitudes' => $solicitudes->toArray(),
        ];
    }

    /**
     * Validar si la hora preferida del cliente tiene conflicto con un técnico específico
     */
    public function validarHoraPreferida(string $uuid, int $idTecnico): array
    {
        $solicitud = Solicitud::where('uuid_solicitud', $uuid)->firstOrFail();

        if (!$solicitud->fecha_preferida || !$solicitud->hora_preferida) {
            return [
                'tiene_conflicto' => false,
                'mensaje' => 'El cliente no propuso una fecha/hora preferida.',
                'fecha_preferida' => null,
                'hora_preferida' => null,
            ];
        }

        $resultado = DB::select(
            'CALL sp_verificar_conflicto_coordinacion(?, ?, ?, ?)',
            [
                $idTecnico,
                $solicitud->fecha_preferida,
                $solicitud->hora_preferida,
                $uuid
            ]
        );

        return [
            'tiene_conflicto' => $resultado[0]->tiene_conflicto == 1,
            'mensaje' => $resultado[0]->mensaje_conflicto,
            'fecha_preferida' => $solicitud->fecha_preferida,
            'hora_preferida' => $solicitud->hora_preferida,
            'hora_conflicto' => $resultado[0]->hora_coordinada_conflicto ?? null,
            'direccion_conflicto' => $resultado[0]->direccion_conflicto ?? null,
        ];
    }

    public function listarCotizaciones(?string $estado = null): Collection
    {
        $query = Cotizacion::with([
            'solicitud:id_cliente,id_tecnico,estado,direccion_servicio',
            'solicitud.cliente.usuario:id_usuario,nombres,apellidos',
            'detalles.itemCatalogo:id_item,nombre,sku_codigo'
        ]);

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * ✅ NUEVO: Obtener historial completo de servicios del técnico autenticado
     */
    public function obtenerHistorialTecnico(Usuario $usuario): array
    {
        $tecnico = $usuario->perfilTecnico;
        if (!$tecnico) {
            throw new Exception('El usuario no tiene perfil de técnico.');
        }

        $solicitudes = Solicitud::with([
            'cliente.usuario:id_usuario,nombres,apellidos',
            'cotizacion:id_cotizacion,uuid_solicitud,estado,subtotal,igv,total',
            'pagos:id_pago,uuid_solicitud,monto_pagado,estado_pago,tipo_pago,fecha_pago'
        ])
            ->where('id_tecnico', $tecnico->id_tecnico)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado' => $solicitud->estado,
                    'descripcion_problema' => $solicitud->descripcion_problema,
                    'direccion_servicio' => $solicitud->direccion_servicio,
                    'latitud' => $solicitud->latitud ? (float) $solicitud->latitud : null,
                    'longitud' => $solicitud->longitud ? (float) $solicitud->longitud : null,
                    'fecha_creacion' => $solicitud->created_at?->format('Y-m-d H:i:s'),
                    'fecha_coordinada' => $solicitud->fecha_coordinada?->format('Y-m-d'),
                    'hora_coordinada' => $solicitud->hora_coordinada,
                    'cliente' => $solicitud->cliente ? [
                        'nombre_completo' => trim(
                            $solicitud->cliente->usuario->nombres . ' ' .
                                $solicitud->cliente->usuario->apellidos
                        ),
                        'telefono' => $solicitud->cliente->telefono ?? null,
                    ] : null,
                    'cotizacion' => $solicitud->cotizacion ? [
                        'estado' => $solicitud->cotizacion->estado,
                        'total' => $solicitud->cotizacion->total,
                    ] : null,
                    'total_pagado' => $solicitud->pagos
                        ->where('estado_pago', 'COMPLETADO')
                        ->sum('monto_pagado'),
                ];
            });

        return [
            'tecnico' => [
                'id_tecnico' => $tecnico->id_tecnico,
                'especialidad' => $tecnico->especialidad,
            ],
            'total_servicios' => $solicitudes->count(),
            'servicios_activos' => $solicitudes->whereIn('estado', ['ASIGNADA', 'EN_PROCESO'])->count(),
            'servicios_finalizados' => $solicitudes->whereIn('estado', ['FINALIZADA', 'PAGADA'])->count(),
            'servicios_cancelados' => $solicitudes->where('estado', 'CANCELADA')->count(),
            'total_ingresos' => $solicitudes->sum('total_pagado'),
            'solicitudes' => $solicitudes->toArray(),
        ];
    }

    /**
     * Listar solicitudes con filtros opcionales
     * ✅ ACTUALIZADO: Con coordenadas, cliente, técnico y cotización
     */
    public function listarSolicitudesFiltradas(?bool $soloUrgentes = null, ?string $estado = null): Collection
    {
        $query = Solicitud::with(['cliente.usuario', 'tecnico.usuario', 'cotizacion']);

        if ($soloUrgentes === true) {
            $query->where('es_urgente', true);
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query
            ->orderBy('es_urgente', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($solicitud) {
                return [
                    'uuid_solicitud' => $solicitud->uuid_solicitud,
                    'estado' => $solicitud->estado,
                    'es_urgente' => $solicitud->es_urgente,
                    'descripcion_problema' => $solicitud->descripcion_problema,
                    'direccion_servicio' => $solicitud->direccion_servicio,
                    'latitud' => $solicitud->latitud ? (float) $solicitud->latitud : null,
                    'longitud' => $solicitud->longitud ? (float) $solicitud->longitud : null,
                    'fecha_creacion' => $solicitud->created_at?->format('Y-m-d H:i:s'),
                    'cliente' => $solicitud->cliente?->usuario ?
                        trim($solicitud->cliente->usuario->nombres . ' ' . $solicitud->cliente->usuario->apellidos) : null,
                    'tecnico' => $solicitud->tecnico?->usuario ?
                        trim($solicitud->tecnico->usuario->nombres . ' ' . $solicitud->tecnico->usuario->apellidos) : null,
                    'cotizacion' => $solicitud->cotizacion ? [
                        'total' => $solicitud->cotizacion->total
                    ] : null
                ];
            });
    }
    
    /**
     * Asigna materiales a la cotización.
     * ✅ CORREGIDO: Ahora crea la cotización si no existe y cambia estado a COTIZADA
     */
    public function asignarItems(string $uuidSolicitud, array $items, int $idUsuarioActual): Solicitud
    {
        DB::beginTransaction();
        try {
            $solicitud = Solicitud::where('uuid_solicitud', $uuidSolicitud)->firstOrFail();

            // ✅ NUEVO: Buscamos o creamos la cotización (más robusto)
            $cotizacion = Cotizacion::firstOrCreate(
                ['uuid_solicitud' => $uuidSolicitud],
                [
                    'estado' => 'BORRADOR',
                    'tasa_igv' => 18.00,
                    'id_usuario_creador' => $idUsuarioActual,
                ]
            );

            // Limpiar detalles anteriores (para evitar duplicados si el técnico reasigna)
            DetalleCotizacion::where('id_cotizacion', $cotizacion->id_cotizacion)->delete();

            // Insertar los nuevos ítems
            foreach ($items as $item) {
                DetalleCotizacion::create([
                    'id_cotizacion' => $cotizacion->id_cotizacion,
                    'id_item' => $item['id_item'],
                    'cantidad' => $item['cantidad'],
                    'precio_aplicado' => $item['precio_aplicado'],
                ]);
            }

            // ✅ CORREGIDO: Cambiar estado de la cotización a ENVIADA
            $cotizacion->update(['estado' => 'ENVIADA']);

            // ✅ CORREGIDO: Cambiar estado de la solicitud a COTIZADA (no ASIGNADA)
            $estadoAnterior = $solicitud->estado;

            // Solo cambiar si no está ya en un estado posterior
            if (in_array($estadoAnterior, ['PENDIENTE', 'ASIGNADA'])) {
                $solicitud->update(['estado' => 'COTIZADA']);
                $this->registrarHistorial($solicitud, $estadoAnterior, 'COTIZADA', $idUsuarioActual);
            }
            
            // ✅ APRENDIZAJE AUTOMÁTICO
            if (isset($datos['id_tipo_trabajo'])) {
                $this->tipoTrabajoService->aprenderDeCotizacion($datos['id_tipo_trabajo'], $items);
            }    
        
            DB::commit();
            return $solicitud->fresh(['cotizacion.detalles.itemCatalogo']);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al asignar ítems: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * ✅ NUEVO: Validar si un técnico está saturado (sin afectar asignarTecnico)
     * GET /api/solicitudes/tecnico/{id_tecnico}/validar-saturacion
     */
    public function validarSaturacionTecnico(int $idTecnico): array
    {
        // Límite máximo de trabajos activos por técnico (configurable en .env)
        $limiteMaximo = (int) env('LIMITE_TRABAJOS_TECNICO', 5);

        $trabajosActivos = Solicitud::where('id_tecnico', $idTecnico)
            ->whereIn('estado', ['ASIGNADA', 'COTIZADA', 'APROBADA', 'EN_PROCESO'])
            ->count();

        // ✅ Calcular trabajos disponibles en variable separada
        $disponibles = $limiteMaximo - $trabajosActivos;

        return [
            'id_tecnico' => $idTecnico,
            'total_trabajos_activos' => $trabajosActivos,
            'limite_maximo' => $limiteMaximo,
            'esta_saturado' => $trabajosActivos >= $limiteMaximo,
            'trabajos_disponibles' => max(0, $disponibles),
            'mensaje' => $trabajosActivos >= $limiteMaximo 
                ? "El técnico está saturado ({$trabajosActivos}/{$limiteMaximo} trabajos activos)."
                : "El técnico tiene capacidad para {$disponibles} trabajo(s) más.",
        ];
    }

}