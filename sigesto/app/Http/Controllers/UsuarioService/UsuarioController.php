<?php

namespace App\Http\Controllers\UsuarioService;

use App\Http\Controllers\Controller;
use App\Services\UsuarioService;
use App\Http\Requests\UsuarioService\RegistroClienteRequest;
use App\Http\Requests\UsuarioService\CrearUsuarioRequest;
use App\Http\Requests\UsuarioService\UpdatePerfilRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UsuarioService\ActualizarUsuarioAdminRequest;

class UsuarioController extends Controller
{
    protected $usuarioService;

    public function __construct(UsuarioService $usuarioService)
    {
        $this->usuarioService = $usuarioService;
    }

    /**
     * ✅ REGISTRO PÚBLICO (Sin token)
     * POST /api/auth/registro-cliente
     */
    public function registroCliente(RegistroClienteRequest $request): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->registrarCliente($request->validated());

            return response()->json([
                'mensaje' => 'Cliente registrado exitosamente. Ya puede iniciar sesión.',
                'data' => $usuario
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al registrar el cliente.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ LISTAR USUARIOS (Solo Admin)
     * GET /api/usuarios
     */
    public function index(): JsonResponse
    {
        try {
            $usuarios = $this->usuarioService->listarUsuarios();

            return response()->json([
                'mensaje' => 'Usuarios obtenidos exitosamente.',
                'data' => $usuarios
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener la lista de usuarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CREAR USUARIO (Solo Admin)
     * POST /api/usuarios
     */
    public function store(CrearUsuarioRequest $request): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->crearUsuario($request->validated());

            return response()->json([
                'mensaje' => 'Usuario creado exitosamente.',
                'data' => $usuario
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al crear el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ VER MI PERFIL (Todos los roles)
     * GET /api/usuarios/perfil
     */
    public function miPerfil(Request $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            $perfil = $this->usuarioService->obtenerMiPerfil($usuario);

            return response()->json([
                'mensaje' => 'Perfil obtenido exitosamente.',
                'data' => $perfil
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener el perfil.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ VER DETALLE DE USUARIO (Todos los roles)
     * GET /api/usuarios/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioService->obtenerDetalleUsuario($id);

            return response()->json([
                'mensaje' => 'Información de usuario obtenida exitosamente.',
                'data' => $usuario
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Usuario no encontrado.',
                'error' => 'El ID proporcionado no existe en el sistema.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener la información del usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ACTUALIZAR MI PERFIL (Todos los roles)
     * PUT /api/usuarios/perfil
     */
    public function actualizarPerfil(UpdatePerfilRequest $request): JsonResponse
    {
        try {
            $usuario = $request->user();
            $perfilActualizado = $this->usuarioService->actualizarPerfil($usuario, $request->validated());

            return response()->json([
                'mensaje' => 'Perfil actualizado exitosamente.',
                'data' => $perfilActualizado
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al actualizar el perfil.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ACTUALIZAR USUARIO (Solo Admin)
     * PUT /api/usuarios/{id}
     */
    public function update(ActualizarUsuarioAdminRequest $request, int $id): JsonResponse
    {
        try {
            $usuarioActualizado = $this->usuarioService->actualizarUsuarioAdmin($id, $request->validated());
            return response()->json([
                'mensaje' => 'Usuario actualizado exitosamente.',
                'data' => $usuarioActualizado
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['mensaje' => 'Usuario no encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error al actualizar.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ ELIMINAR USUARIO (Solo Admin)
     * DELETE /api/usuarios/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->usuarioService->eliminarUsuario($id);

            return response()->json([
                'mensaje' => 'Usuario dado de baja lógicamente (SoftDelete aplicado).'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'mensaje' => 'Usuario no encontrado.',
                'error' => 'El ID proporcionado no existe en el sistema.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al eliminar el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
    
    /**
 * ✅ NUEVO: Listar solo técnicos
 * GET /api/usuarios/tecnicos
 */
public function listarTecnicos(): JsonResponse
{
    try {
        $resultado = $this->usuarioService->listarTecnicos();
        
        return response()->json([
            'mensaje' => 'Técnicos obtenidos exitosamente.',
            'data' => $resultado
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'mensaje' => 'Error al obtener técnicos.',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ NUEVO: Ver carga de trabajo de un técnico
 * GET /api/usuarios/tecnicos/{id}/carga-trabajo
 */
public function cargaTrabajoTecnico(int $id): JsonResponse
{
    try {
        // Verificar que el usuario sea técnico
        $usuario = Usuario::whereHas('perfilTecnico', fn($q) => $q->where('id_tecnico', $id))
            ->with('perfilTecnico')
            ->firstOrFail();

        $resultado = $this->usuarioService->obtenerCargaTrabajoTecnico($id);

        return response()->json([
            'mensaje' => 'Carga de trabajo obtenida exitosamente.',
            'data' => $resultado
        ], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'mensaje' => 'Técnico no encontrado.',
            'error' => 'El ID de técnico proporcionado no existe.'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'mensaje' => 'Error al obtener la carga de trabajo.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
