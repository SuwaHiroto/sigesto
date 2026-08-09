<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  // Ejemplo: 'ADMINISTRADOR', 'TECNICO'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Verificar que el usuario esté autenticado
        if (!$request->user()) {
            return response()->json([
                'mensaje' => 'No autenticado. Por favor, inicie sesión.',
            ], 401);
        }

        // Cargar la relación del rol si no está cargada
        $usuario = $request->user();
        if (!$usuario->relationLoaded('rol')) {
            $usuario->load('rol');
        }

        // Verificar que el usuario tenga un rol
        if (!$usuario->rol) {
            return response()->json([
                'mensaje' => 'El usuario no tiene un rol asignado.',
            ], 403);
        }

        // Verificar si el rol del usuario está en la lista de roles permitidos
        $rolUsuario = $usuario->rol->nombre;
        
        if (!in_array($rolUsuario, $roles)) {
            return response()->json([
                'mensaje' => 'No tiene permisos para realizar esta acción.',
                'rol_requerido' => $roles,
                'rol_actual' => $rolUsuario,
            ], 403);
        }

        return $next($request);
    }
}