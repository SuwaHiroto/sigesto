<?php

namespace App\Services;

use App\Models\Solicitud;
use App\Models\Pago;
use App\Models\Usuario;
use App\Models\Cotizacion;
use App\Models\DetalleCotizacion;
use App\Models\TipoTrabajo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ReporteService
{
    /**
     * Dashboard principal: Resumen general del negocio
     */
    public function obtenerDashboard(): array
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $inicioAnio = Carbon::now()->startOfYear();

        // Solicitudes
        $totalSolicitudes = Solicitud::count();
        $solicitudesHoy = Solicitud::whereDate('created_at', $hoy)->count();
        $solicitudesMes = Solicitud::where('created_at', '>=', $inicioMes)->count();
        $solicitudesUrgentes = Solicitud::where('es_urgente', true)->count();

        // Solicitudes por estado
        $solicitudesPorEstado = Solicitud::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Pagos
        $totalIngresos = Pago::where('estado_pago', 'COMPLETADO')->sum('monto_pagado');
        $ingresosMes = Pago::where('estado_pago', 'COMPLETADO')
            ->where('fecha_pago', '>=', $inicioMes)
            ->sum('monto_pagado');
        $ingresosAnio = Pago::where('estado_pago', 'COMPLETADO')
            ->where('fecha_pago', '>=', $inicioAnio)
            ->sum('monto_pagado');
        $pagosPendientes = Pago::where('estado_pago', 'PENDIENTE_APROBACION')->count();

        // Técnicos y Clientes
        $totalTecnicos = Usuario::whereHas('rol', fn($q) => $q->where('nombre', 'TECNICO'))->count();
        $totalClientes = Usuario::whereHas('rol', fn($q) => $q->where('nombre', 'CLIENTE'))->count();

        return [
            'solicitudes' => [
                'total' => $totalSolicitudes,
                'hoy' => $solicitudesHoy,
                'este_mes' => $solicitudesMes,
                'urgentes' => $solicitudesUrgentes,
                'por_estado' => $solicitudesPorEstado,
            ],
            'finanzas' => [
                'total_ingresos' => round($totalIngresos, 2),
                'ingresos_mes' => round($ingresosMes, 2),
                'ingresos_anio' => round($ingresosAnio, 2),
                'pagos_pendientes' => $pagosPendientes,
            ],
            'usuarios' => [
                'total_tecnicos' => $totalTecnicos,
                'total_clientes' => $totalClientes,
            ],
        ];
    }

    /**
     * MEJORA 1 y 5: Reporte de ingresos con filtros de fecha y tendencia diaria
     */
    public function obtenerReporteIngresos(string $periodo = 'mes', ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $query = Pago::where('estado_pago', 'COMPLETADO');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
        } else {
            switch ($periodo) {
                case 'dia': $query->whereDate('fecha_pago', Carbon::today()); break;
                case 'semana': $query->where('fecha_pago', '>=', Carbon::now()->startOfWeek()); break;
                case 'mes': $query->where('fecha_pago', '>=', Carbon::now()->startOfMonth()); break;
                case 'anio': $query->where('fecha_pago', '>=', Carbon::now()->startOfYear()); break;
            }
        }

        $totalIngresos = $query->sum('monto_pagado');
        $totalTransacciones = $query->count();

        $ingresosPorMetodo = (clone $query)
            ->select('metodo_pago', DB::raw('SUM(monto_pagado) as total'))
            ->groupBy('metodo_pago')
            ->get()
            ->map(fn($item) => ['metodo_pago' => $item->metodo_pago, 'total' => round($item->total, 2)])
            ->toArray();

        $ingresosPorTipo = (clone $query)
            ->select('tipo_pago', DB::raw('SUM(monto_pagado) as total'))
            ->groupBy('tipo_pago')
            ->get()
            ->map(fn($item) => ['tipo_pago' => $item->tipo_pago, 'total' => round($item->total, 2)])
            ->toArray();

        // Tendencia de los últimos 30 días (o del período seleccionado)
        $diasTendencia = $fechaInicio && $fechaFin 
            ? Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin)) 
            : 30;

        $ingresosDiarios = Pago::where('estado_pago', 'COMPLETADO')
            ->where('fecha_pago', '>=', Carbon::now()->subDays($diasTendencia))
            ->select(DB::raw('DATE(fecha_pago) as fecha'), DB::raw('SUM(monto_pagado) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get()
            ->map(fn($item) => ['fecha' => $item->fecha, 'total' => round($item->total, 2)])
            ->toArray();

        return [
            'periodo' => $periodo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'total_ingresos' => round($totalIngresos, 2),
            'total_transacciones' => $totalTransacciones,
            'ingresos_por_metodo' => $ingresosPorMetodo,
            'ingresos_por_tipo' => $ingresosPorTipo,
            'ingresos_diarios' => $ingresosDiarios, // Para gráficos de línea en el frontend
        ];
    }

    /**
     * MEJORA 1: Reporte de solicitudes con filtros de fecha
     */
    public function obtenerReporteSolicitudes(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $query = Solicitud::query();

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('solicitudes.created_at', [$fechaInicio, $fechaFin]);
        }

        $totalSolicitudes = (clone $query)->count();

        $porEstado = (clone $query)
            ->select('solicitudes.estado', DB::raw('count(*) as total'))
            ->groupBy('solicitudes.estado')
            ->pluck('total', 'estado')
            ->toArray();

        $urgentes = (clone $query)->where('solicitudes.es_urgente', true)->count();
        $normales = $totalSolicitudes - $urgentes;

        $porTecnico = (clone $query)
            ->whereNotNull('solicitudes.id_tecnico')
            ->join('perfiles_tecnicos', 'solicitudes.id_tecnico', '=', 'perfiles_tecnicos.id_tecnico')
            ->join('usuarios', 'perfiles_tecnicos.id_usuario', '=', 'usuarios.id_usuario')
            ->select(
                'perfiles_tecnicos.id_tecnico',
                DB::raw('CONCAT(usuarios.nombres, " ", usuarios.apellidos) as nombre_tecnico'),
                DB::raw('count(solicitudes.uuid_solicitud) as total')
            )
            ->groupBy('perfiles_tecnicos.id_tecnico', 'usuarios.nombres', 'usuarios.apellidos')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'id_tecnico' => $item->id_tecnico,
                'nombre_tecnico' => $item->nombre_tecnico,
                'total_solicitudes' => $item->total,
            ])
            ->toArray();

        $tiempoPromedio = Solicitud::whereIn('estado', ['FINALIZADA', 'PAGADA'])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as promedio_horas')
            ->value('promedio_horas');

        return [
            'total_solicitudes' => $totalSolicitudes,
            'por_estado' => $porEstado,
            'urgentes' => $urgentes,
            'normales' => $normales,
            'por_tecnico' => $porTecnico,
            'tiempo_promedio_resolucion_horas' => round($tiempoPromedio ?? 0, 2),
        ];
    }
    
        /**
     * Reporte de técnicos con productividad, ingresos y estado de saturación
     * ✅ CORREGIDO: Parámetros de fecha definidos + Métricas avanzadas intactas
     */
    public function obtenerReporteTecnicos(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        // 1. Obtener todos los usuarios con rol técnico
        $tecnicos = Usuario::whereHas('rol', fn($q) => $q->where('nombre', 'TECNICO'))
            ->with('perfilTecnico')
            ->get();

        $reporte = $tecnicos->map(function ($tecnico) {
            $idTecnico = $tecnico->perfilTecnico?->id_tecnico;
            if (!$idTecnico) return null;

            // 2. Trabajos completados en el período (o mes actual si no hay filtro)
            $queryCompletados = Solicitud::where('id_tecnico', $idTecnico)->where('estado', 'FINALIZADA');
            if ($fechaInicio && $fechaFin) {
                $queryCompletados->whereBetween('updated_at', [$fechaInicio, $fechaFin]);
            } else {
                $queryCompletados->whereMonth('updated_at', now()->month);
            }
            $trabajosCompletados = $queryCompletados->count();

            // 3. Ingresos generados en el mismo período
            $queryIngresos = Solicitud::where('id_tecnico', $idTecnico)
                ->where('estado', 'FINALIZADA')
                ->join('cotizaciones', 'solicitudes.uuid_solicitud', '=', 'cotizaciones.uuid_solicitud');
            
            if ($fechaInicio && $fechaFin) {
                $queryIngresos->whereBetween('solicitudes.updated_at', [$fechaInicio, $fechaFin]);
            } else {
                $queryIngresos->whereMonth('solicitudes.updated_at', now()->month);
            }
            $ingresosGenerados = $queryIngresos->sum('cotizaciones.total');

            // 4. Trabajos activos actuales (para detectar saturación)
            // ✅ Incluye 'PAGADA' para que el técnico pueda verlos y finalizarlos
            $trabajosActivos = Solicitud::where('id_tecnico', $idTecnico)
                ->whereIn('estado', ['ASIGNADA', 'APROBADA', 'EN_PROCESO', 'PAGADA'])
                ->count();

            return [
                'id' => $tecnico->id_usuario,
                'nombre' => trim($tecnico->nombres . ' ' . $tecnico->apellidos),
                'especialidad' => $tecnico->perfilTecnico?->especialidad ?? 'N/A',
                'trabajos_periodo' => $trabajosCompletados,
                'ingresos_generados' => round($ingresosGenerados, 2),
                'trabajos_activos' => $trabajosActivos,
                'estado' => $trabajosActivos >= 5 ? 'SATURADO' : 'DISPONIBLE',
            ];
        })->filter()->sortByDesc('trabajos_periodo')->values();

        return [
            'total_tecnicos' => $tecnicos->count(),
            'tecnicos' => $reporte->toArray(),
        ];
    }

    /**
     * Reporte de Cotizaciones (tasa de conversión y análisis)
     */
    public function obtenerReporteCotizaciones(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $query = Cotizacion::query();

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        $totalCotizaciones = (clone $query)->count();

        $porEstado = (clone $query)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $enviadas = ($porEstado['ENVIADA'] ?? 0) + ($porEstado['APROBADA'] ?? 0) + ($porEstado['LIQUIDADA'] ?? 0) + ($porEstado['RECHAZADA'] ?? 0);
        $aprobadas = ($porEstado['APROBADA'] ?? 0) + ($porEstado['LIQUIDADA'] ?? 0);
        $rechazadas = $porEstado['RECHAZADA'] ?? 0;
        $tasaConversion = $enviadas > 0 ? round(($aprobadas / $enviadas) * 100, 2) : 0;

        $valorPromedio = (clone $query)->avg('total');
        $valorMaximo = (clone $query)->max('total');
        $valorMinimo = (clone $query)->min('total');
        $valorTotal = (clone $query)->sum('total');

        $topCotizaciones = (clone $query)
            ->where('total', '>', 0)
            ->with(['solicitud:id_cliente,id_tecnico,estado,descripcion_problema'])
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id_cotizacion' => $c->id_cotizacion,
                'uuid_solicitud' => $c->uuid_solicitud,
                'total' => round($c->total, 2),
                'estado' => $c->estado,
                'fecha' => $c->created_at?->format('Y-m-d'),
                'cliente' => $c->solicitud?->cliente?->usuario
                    ? trim($c->solicitud->cliente->usuario->nombres . ' ' . $c->solicitud->cliente->usuario->apellidos)
                    : null,
            ])
            ->toArray();

        $tiempoPromedio = Cotizacion::whereIn('estado', ['APROBADA', 'LIQUIDADA'])
            ->whereNotNull('updated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as promedio_horas')
            ->value('promedio_horas');

        return [
            'total_cotizaciones' => $totalCotizaciones,
            'por_estado' => $porEstado,
            'tasa_conversion' => $tasaConversion . '%',
            'aprobadas' => $aprobadas,
            'rechazadas' => $rechazadas,
            'enviadas_pendientes' => $porEstado['ENVIADA'] ?? 0,
            'valores' => [
                'total_facturado' => round($valorTotal, 2),
                'promedio' => round($valorPromedio ?? 0, 2),
                'maximo' => round($valorMaximo ?? 0, 2),
                'minimo' => round($valorMinimo ?? 0, 2),
            ],
            'tiempo_promedio_aprobacion_horas' => round($tiempoPromedio ?? 0, 2),
            'top_cotizaciones' => $topCotizaciones,
        ];
    }

    /**
     * Reporte de Tipos de Trabajo (popularidad y uso)
     */
    public function obtenerReporteTiposTrabajo(): array
    {
        $tiposTrabajo = TipoTrabajo::withCount('itemsSugeridos')
            ->orderByDesc('items_sugeridos_count')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
                'descripcion' => $t->descripcion,
                'activo' => $t->activo,
                'total_items_sugeridos' => $t->items_sugeridos_count,
                'valor_estimado' => round(
                    $t->itemsSugeridos->sum(fn($i) => $i->precio_ref * $i->pivot->cantidad_sugerida),
                    2
                ),
            ])
            ->toArray();

        $topItemsCotizados = DetalleCotizacion::select(
            'items_catalogo.id_item',
            'items_catalogo.nombre',
            'items_catalogo.tipo_item',
            'items_catalogo.sku_codigo',
            DB::raw('SUM(detalle_cotizacion.cantidad) as total_cantidad'),
            DB::raw('COUNT(DISTINCT detalle_cotizacion.id_cotizacion) as veces_cotizado')
        )
            ->join('items_catalogo', 'detalle_cotizacion.id_item', '=', 'items_catalogo.id_item')
            ->groupBy('items_catalogo.id_item', 'items_catalogo.nombre', 'items_catalogo.tipo_item', 'items_catalogo.sku_codigo')
            ->orderByDesc('total_cantidad')
            ->limit(15)
            ->get()
            ->map(fn($i) => [
                'id_item' => $i->id_item,
                'sku_codigo' => $i->sku_codigo,
                'nombre' => $i->nombre,
                'tipo_item' => $i->tipo_item,
                'total_cantidad' => (float) $i->total_cantidad,
                'veces_cotizado' => $i->veces_cotizado,
            ])
            ->toArray();

        $distribucionTipo = DetalleCotizacion::select(
            'items_catalogo.tipo_item',
            DB::raw('SUM(detalle_cotizacion.cantidad * detalle_cotizacion.precio_aplicado) as total_facturado'),
            DB::raw('COUNT(*) as total_items')
        )
            ->join('items_catalogo', 'detalle_cotizacion.id_item', '=', 'items_catalogo.id_item')
            ->groupBy('items_catalogo.tipo_item')
            ->get()
            ->map(fn($d) => [
                'tipo_item' => $d->tipo_item,
                'total_facturado' => round($d->total_facturado, 2),
                'total_items' => $d->total_items,
            ])
            ->toArray();

        return [
            'total_tipos_trabajo' => count($tiposTrabajo),
            'tipos_trabajo' => $tiposTrabajo,
            'top_items_cotizados' => $topItemsCotizados,
            'distribucion_tipo_item' => $distribucionTipo,
        ];
    }

    /**
     * MEJORA 4: Reporte de Pagos Pendientes de Aprobación + Deuda Real de clientes
     */
    public function obtenerReportePagosPendientes(): array
    {
        // A. Pagos pendientes de aprobación administrativa
        $pagosPorAprobar = Pago::where('estado_pago', 'PENDIENTE_APROBACION')
            ->with(['solicitud.cliente.usuario'])
            ->orderBy('fecha_pago', 'asc')
            ->get()
            ->map(function ($pago) {
                $diasPendiente = $pago->fecha_pago ? Carbon::parse($pago->fecha_pago)->diffInDays(now()) : 0;
                return [
                    'id_pago' => $pago->id_pago,
                    'uuid_solicitud' => $pago->uuid_solicitud,
                    'monto' => round($pago->monto_pagado, 2),
                    'cliente' => $pago->solicitud?->cliente?->usuario 
                        ? trim($pago->solicitud->cliente->usuario->nombres . ' ' . $pago->solicitud->cliente->usuario->apellidos) 
                        : 'N/A',
                    'dias_pendiente' => $diasPendiente,
                    'tipo' => 'POR_APROBAR'
                ];
            });

        // B. DEUDA REAL: Solicitudes FINALIZADAS o EN_PROCESO con saldo pendiente
        $deudasReales = Solicitud::with(['cotizacion', 'cliente.usuario'])
            ->whereIn('estado', ['FINALIZADA', 'EN_PROCESO'])
            ->get()
            ->map(function ($s) {
                $totalPagado = Pago::where('uuid_solicitud', $s->uuid_solicitud)
                    ->where('estado_pago', 'COMPLETADO')
                    ->sum('monto_pagado');
                
                $deuda = ($s->cotizacion->total ?? 0) - $totalPagado;
                
                if ($deuda <= 0) return null;

                return [
                    'uuid_solicitud' => $s->uuid_solicitud,
                    'cliente' => trim($s->cliente->usuario->nombres . ' ' . $s->cliente->usuario->apellidos),
                    'direccion' => $s->direccion_servicio,
                    'estado' => $s->estado,
                    'total_cotizacion' => $s->cotizacion->total,
                    'total_pagado' => $totalPagado,
                    'deuda' => round($deuda, 2),
                    'fecha_actualizacion' => $s->updated_at?->format('Y-m-d'),
                    'dias_mora' => now()->diffInDays($s->updated_at),
                    'tipo' => 'DEUDA_PENDIENTE'
                ];
            })
            ->filter()
            ->sortByDesc('deuda')
            ->values();

        return [
            'resumen' => [
                'pagos_por_aprobar' => $pagosPorAprobar->count(),
                'monto_por_aprobar' => round($pagosPorAprobar->sum('monto'), 2),
                'solicitudes_con_deuda' => $deudasReales->count(),
                'deuda_total_acumulada' => round($deudasReales->sum('deuda'), 2),
            ],
            'pagos_por_aprobar' => $pagosPorAprobar->toArray(),
            'deudas_reales' => $deudasReales->toArray(),
        ];
    }
    
    /**
     * Dashboard del Técnico
     */
    public function obtenerDashboardTecnico(Usuario $usuario): array
    {
        $tecnico = $usuario->perfilTecnico;
        if (!$tecnico) {
            throw new Exception('El usuario no tiene perfil de técnico.');
        }

        $idTecnico = $tecnico->id_tecnico;
        $hoy = now()->format('Y-m-d');
        $inicioMes = now()->startOfMonth()->format('Y-m-d');

        $trabajosActivos = Solicitud::where('id_tecnico', $idTecnico)
            ->whereIn('estado', ['ASIGNADA', 'APROBADA', 'EN_PROCESO', 'PAGADA'])
            ->count();

        $completadosHoy = Solicitud::where('id_tecnico', $idTecnico)
            ->where('estado', 'FINALIZADA')
            ->whereDate('updated_at', $hoy)
            ->count();

        $trabajosMes = Solicitud::where('id_tecnico', $idTecnico)
            ->where('estado', 'FINALIZADA')
            ->whereDate('updated_at', '>=', $inicioMes)
            ->count();

        $pagosPendientesData = Solicitud::with('cotizacion')
            ->where('id_tecnico', $idTecnico)
            ->where('estado', 'FINALIZADA')
            ->get()
            ->map(function ($s) {
                $totalPagado = Pago::where('uuid_solicitud', $s->uuid_solicitud)
                    ->where('estado_pago', 'COMPLETADO')
                    ->sum('monto_pagado');
                $pendiente = ($s->cotizacion->total ?? 0) - $totalPagado;
                return $pendiente > 0 ? ['uuid' => $s->uuid_solicitud, 'pendiente' => $pendiente] : null;
            })
            ->filter()
            ->values();

        $totalPendiente = $pagosPendientesData->sum('pendiente');

        $hojaRutaHoy = Solicitud::with('cliente.usuario')
            ->where('id_tecnico', $idTecnico)
            ->whereIn('estado', ['ASIGNADA', 'APROBADA', 'EN_PROCESO', 'PAGADA'])
            ->where('fecha_coordinada', $hoy)
            ->orderBy('hora_coordinada', 'asc')
            ->get()
            ->map(fn($s) => [
                'uuid_solicitud' => $s->uuid_solicitud,
                'estado' => $s->estado,
                'direccion' => $s->direccion_servicio,
                'hora' => $s->hora_coordinada,
                'cliente' => trim($s->cliente->usuario->nombres . ' ' . $s->cliente->usuario->apellidos),
            ]);

        return [
            'resumen' => [
                'trabajos_activos' => $trabajosActivos,
                'completados_hoy' => $completadosHoy,
                'trabajos_mes' => $trabajosMes,
                'total_pendiente_cobro' => round($totalPendiente, 2),
            ],
            'hoja_ruta_hoy' => $hojaRutaHoy->toArray(),
            'alertas_pagos' => [
                'cantidad' => $pagosPendientesData->count(),
                'total' => round($totalPendiente, 2),
            ]
        ];
    }
}