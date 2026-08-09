<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthService\AuthController;
use App\Http\Controllers\CatalogoService\CatalogoController;
use App\Http\Controllers\SolicitudService\SolicitudController;
use App\Http\Controllers\EvidenciaService\EvidenciaController;
use App\Http\Controllers\FinanzasService\FinanzasController;
use App\Http\Controllers\UsuarioService\UsuarioController;
use App\Http\Controllers\ReporteService\ReporteController;
use App\Http\Controllers\CatalogoService\TipoTrabajoController;

// Login que da el token necesario para las siguientes rutas
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/registrar', [UsuarioController::class, 'registroCliente']);
});

// Rutas Protegidas (Requieren token)
Route::middleware('auth:sanctum')->group(function () {

    //Cerrar sesión (Logout)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

        // CATALOGO
    Route::prefix('catalogo')->group(function () {
        // ✅ Rutas existentes de items_catalogo
        Route::get('/', [CatalogoController::class, 'index'])->middleware('role:ADMINISTRADOR,TECNICO');
        Route::post('/', [CatalogoController::class, 'store'])->middleware('role:ADMINISTRADOR');
        Route::get('/buscar', [CatalogoController::class, 'buscar'])
            ->middleware('role:ADMINISTRADOR,TECNICO');
        Route::patch('/{id}/reactivar', [CatalogoController::class, 'reactivar'])
            ->middleware('role:ADMINISTRADOR');
        Route::put('/{id}', [CatalogoController::class, 'update'])->middleware('role:ADMINISTRADOR');
        Route::delete('/{id}', [CatalogoController::class, 'destroy'])->middleware('role:ADMINISTRADOR');

        Route::post('/vincular-material', [CatalogoController::class, 'vincularMaterial'])
            ->middleware('role:ADMINISTRADOR');
        Route::get('/servicio/{id}/materiales', [CatalogoController::class, 'verMaterialesDeServicio'])
            ->middleware('role:ADMINISTRADOR,TECNICO');

        // ✅ Tipos de Trabajo (Plantillas de servicios)
        Route::prefix('tipos-trabajo')->group(function () {
            Route::get('/', [TipoTrabajoController::class, 'index'])
                ->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');
            Route::get('/{id}/materiales-sugeridos', [TipoTrabajoController::class, 'materialesSugeridos'])
                ->middleware('role:ADMINISTRADOR,TECNICO');
        });

        // ✅ Estadísticas de aprendizaje (Ruta corta, sin prefix extra, método corregido)
        Route::get('/tipos-trabajo/{id}/estadisticas', [TipoTrabajoController::class, 'estadisticas'])
            ->middleware('role:ADMINISTRADOR');

    }); 

    //USUARIO
    Route::prefix('usuarios')->group(function () {

        // Rutas de "Mi Perfil" (Todos los roles pueden ver/editar su propio perfil)
        Route::get('/perfil', [UsuarioController::class, 'miPerfil'])->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');
        Route::put('/perfil', [UsuarioController::class, 'actualizarPerfil'])->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');
        // ✅ NUEVO: Listar solo técnicos (Solo Admin)
        Route::get('/tecnicos', [UsuarioController::class, 'listarTecnicos'])->middleware('role:ADMINISTRADOR');
        // Rutas de Administración (Solo el ADMINISTRADOR puede gestionar usuarios)
        Route::get('/', [UsuarioController::class, 'index'])->middleware('role:ADMINISTRADOR');
        Route::post('/', [UsuarioController::class, 'store'])->middleware('role:ADMINISTRADOR');
        Route::get('/{id}', [UsuarioController::class, 'show'])->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE'); // REVISION
        Route::put('/{id}', [UsuarioController::class, 'update'])->middleware('role:ADMINISTRADOR');
        Route::delete('/{id}', [UsuarioController::class, 'destroy'])->middleware('role:ADMINISTRADOR');
    });

    // SOLICITUDES
    Route::prefix('solicitudes')->group(function () {

        // 1. Crear y listar
        Route::post('/', [SolicitudController::class, 'store'])->middleware('role:CLIENTE');
        Route::get('/', [SolicitudController::class, 'index'])->middleware('role:ADMINISTRADOR');

        // 2. ✅ TODAS LAS RUTAS ESPECÍFICAS PRIMERO (sin {uuid} al inicio)
        Route::get('/asignadas', [SolicitudController::class, 'hojaRuta'])
            ->middleware('role:ADMINISTRADOR,TECNICO');

        Route::get('/mi-historial', [SolicitudController::class, 'miHistorial'])
            ->middleware('role:TECNICO');

        Route::get('/mis-solicitudes', [SolicitudController::class, 'misSolicitudes'])
            ->middleware('role:CLIENTE');

        // ✅ MOVER AQUÍ (antes de /{uuid})
        Route::get('/cliente/{id_cliente}', [SolicitudController::class, 'historialCliente'])
            ->middleware('role:ADMINISTRADOR');
        
        Route::get('/tecnico/{id_tecnico}/carga', [SolicitudController::class, 'validarSaturacionTecnico'])
            ->middleware('role:ADMINISTRADOR');
        
        // 3. Rutas con parámetros específicos
        Route::patch('/{uuid}/estado', [SolicitudController::class, 'cambiarEstado'])
            ->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');
        Route::patch('/{uuid}/tecnico', [SolicitudController::class, 'asignarTecnico'])
            ->middleware('role:ADMINISTRADOR');
        Route::put('/{uuid}', [SolicitudController::class, 'update'])
            ->middleware('role:ADMINISTRADOR,TECNICO');
        Route::get('/{uuid}/validar-hora-preferida/{id_tecnico}', [SolicitudController::class, 'validarHoraPreferida'])
            ->middleware('role:ADMINISTRADOR');

        // 4. ✅ RUTAS GENÉRICAS AL FINAL
        Route::get('/{uuid}', [SolicitudController::class, 'show'])
            ->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');
        Route::delete('/{uuid}', [SolicitudController::class, 'destroy'])
            ->middleware('role:ADMINISTRADOR');
    });

    // EVIDENCIAS
    Route::prefix('evidencias')->group(function () {
        // Subir evidencia (Solo Admin y Técnico)
        Route::post('/subir', [EvidenciaController::class, 'store'])
            ->middleware('role:ADMINISTRADOR,TECNICO');

        // ✅ NUEVO: Ver evidencias de una solicitud (Todos los roles)
        Route::get('/solicitud/{uuid}', [EvidenciaController::class, 'porSolicitud'])
            ->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');

        // ✅ NUEVO: Eliminar evidencia equivocada (Solo Admin y Técnico)
        Route::delete('/{id}', [EvidenciaController::class, 'destroy'])
            ->middleware('role:ADMINISTRADOR,TECNICO');
    });

    // FINANZAS
    Route::prefix('finanzas')->group(function () {
        // Reporte financiero global (Solo Admin)
        Route::get('/', [FinanzasController::class, 'index'])
            ->middleware('role:ADMINISTRADOR');

        Route::get('/mis-pagos', [FinanzasController::class, 'misPagos'])
            ->middleware('role:CLIENTE');

        // Ver historial de pagos de una solicitud (Todos los roles)
        Route::get('/solicitud/{uuid}', [FinanzasController::class, 'porSolicitud'])
            ->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');

        // ✅ NUEVO: Obtener datos de la cuenta bancaria (Todos los autenticados)
        Route::get('/cuenta-bancaria', [FinanzasController::class, 'obtenerCuentaBancaria'])
            ->middleware('role:ADMINISTRADOR,TECNICO,CLIENTE');
        
    
        // ✅ NUEVO: Resumen de pagos (saldo pendiente) - DEBE IR ANTES DE /{id_pago}/verificar
        Route::get('/{uuid}/resumen', [FinanzasController::class, 'resumenPagos'])
            ->middleware('role:ADMINISTRADOR,TECNICO');

        // ✅ NUEVO: Cliente registra el adelanto (Solo Cliente)
        Route::post('/{uuid}/adelanto', [FinanzasController::class, 'registrarAdelanto'])
            ->middleware('role:CLIENTE');

        // ✅ NUEVO: Admin verifica y aprueba el pago (Solo Admin)
        Route::patch('/{id_pago}/verificar', [FinanzasController::class, 'verificarPago'])
            ->middleware('role:ADMINISTRADOR');
        
        Route::patch('/{id_pago}/rechazar', [FinanzasController::class, 'rechazarPago'])
            ->middleware('role:ADMINISTRADOR');
        
        // Registrar un pago final (Solo Admin y Técnico)
        Route::post('/pagar', [FinanzasController::class, 'store'])
            ->middleware('role:ADMINISTRADOR,TECNICO');
    });

    // REPORTES
    Route::prefix('reportes')->group(function () {
        
        // 1. Rutas exclusivas del ADMINISTRADOR
        Route::middleware('role:ADMINISTRADOR')->group(function () {
            Route::get('/dashboard', [ReporteController::class, 'dashboard']);
            Route::get('/ingresos', [ReporteController::class, 'ingresos']);
            Route::get('/solicitudes', [ReporteController::class, 'solicitudes']);
            Route::get('/tecnicos', [ReporteController::class, 'tecnicos']);
            Route::get('/cotizaciones', [ReporteController::class, 'cotizaciones']);
            Route::get('/tipos-trabajo', [ReporteController::class, 'tiposTrabajo']);
            Route::get('/pagos-pendientes', [ReporteController::class, 'pagosPendientes']);
        });

        // 2. ✅ NUEVO ENDPOINT: Dashboard del TÉCNICO (FUERA del grupo de Admin)
        Route::get('/dashboard-tecnico', [ReporteController::class, 'dashboardTecnico'])
            ->middleware('role:TECNICO');
            
    });

});