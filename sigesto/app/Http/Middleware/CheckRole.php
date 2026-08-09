<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['mensaje' => 'No autenticado.'], 401);
        }

        // 👇 FORZAR CARGA DE LA RELACIÓN
        $user->load('rol');

        $userRole = $user->rol->nombre ?? null;

        if (!$userRole) {
            return response()->json([
                'mensaje' => 'El usuario no tiene un rol asignado en el sistema.',
            ], 403);
        }

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'mensaje' => 'Acceso denegado. No tienes los permisos necesarios.',
                'rol_requerido' => $roles,
                'tu_rol_actual' => $userRole
            ], 403);
        }

        return $next($request);
    }
}
