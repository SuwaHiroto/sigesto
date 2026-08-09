<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Inicia sesión y genera token de autenticación.
     * 
     * @param string $email Correo del usuario
     * @param string $password Contraseña en texto plano
     * @return array Datos del usuario y token
     * @throws ValidationException Si las credenciales son incorrectas
     */
    public function login(string $email, string $password): array
    {
        // 1. Buscar el usuario y cargar su rol
        $usuario = Usuario::with('rol')->where('email', $email)->first();

        // 2. Validar credenciales (mensaje genérico por seguridad)
        if (!$usuario || !Hash::check($password, $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // 3. Generar el Token de Sanctum
        $token = $usuario->createToken('sigesto-app')->plainTextToken;

        // 4. Retornar la respuesta estructurada
        return [
            'mensaje' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id_usuario,
                'nombre_completo' => trim($usuario->nombres . ' ' . $usuario->apellidos),
                'email' => $usuario->email,
                'id_rol' => $usuario->id_rol,
                'nombre_rol' => $usuario->rol?->nombre ?? 'Sin rol',
            ]
        ];
    }

    /**
     * Cierra la sesión eliminando el token actual.
     * 
     * @param Usuario $usuario Usuario autenticado
     * @return void
     */
    public function logout(Usuario $usuario): void
    {
        /** @var PersonalAccessToken $token */
        $token = $usuario->currentAccessToken();
        $token->delete();
    }
}
