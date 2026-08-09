<?php

namespace App\Http\Controllers\AuthService;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthService\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Inyección de dependencias del Servicio
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Llamamos al "cerebro"
            $resultado = $this->authService->login(
                $request->email,
                $request->password
            );

            return response()->json($resultado, 200);
        } catch (ValidationException $e) {
            // Retorna 422 con los mensajes de error del Form Request o del Service
            return response()->json([
                'mensaje' => 'Error de autenticación',
                'errores' => $e->errors()
            ], 422);
        }
    }

    /**
     * POST /api/auth/logout
     * Requiere middleware auth:sanctum
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.'
        ], 200);
    }
}
