<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\PerfilCliente;
use App\Models\PerfilTecnico;
use App\Models\PerfilAdmin;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioService
{
    /**
     * ✅ TU MÉTODO ORIGINAL (sin cambios)
     * Obtiene la información detallada de un usuario y su perfil asociado.
     */
    public function obtenerDetalleUsuario(int $id): array
    {
        $usuario = Usuario::with(['rol', 'perfilAdmin', 'perfilTecnico', 'perfilCliente'])
            ->findOrFail($id);

        $perfilDatos = null;
        $tipoPerfil = 'SIN_PERFIL';

        if ($usuario->perfilCliente) {
            $perfilDatos = $usuario->perfilCliente;
            $tipoPerfil = 'CLIENTE';
        } elseif ($usuario->perfilTecnico) {
            $perfilDatos = $usuario->perfilTecnico;
            $tipoPerfil = 'TECNICO';
        } elseif ($usuario->perfilAdmin) {
            $perfilDatos = $usuario->perfilAdmin;
            $tipoPerfil = 'ADMIN';
        }

        return [
            'id_usuario' => $usuario->id_usuario,
            'nombre_completo' => trim($usuario->nombres . ' ' . $usuario->apellidos),
            'email' => $usuario->email,
            'rol' => $usuario->rol?->nombre ?? 'Sin rol',
            'tipo_perfil' => $tipoPerfil,
            'datos_perfil' => $perfilDatos,
            'created_at' => $usuario->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * ✅ NUEVO: Ver Mi Perfil (sin necesidad de pasar ID)
     */
    public function obtenerMiPerfil(Usuario $usuario): array
    {
        // ✅ obtenerDetalleUsuario() ya hace el with() internamente
        return $this->obtenerDetalleUsuario($usuario->id_usuario);
    }

    /**
     * ✅ NUEVO: Registro Público (Solo Clientes)
     * El frontend no envía id_rol, se hardcodea por seguridad.
     */
    public function registrarCliente(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear usuario con rol CLIENTE hardcodeado
            $usuario = Usuario::create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'id_rol' => 3, // ⚠️ Ajusta este ID según tu tabla roles (debe ser CLIENTE)

            ]);

            // 2. Crear perfil de cliente
            PerfilCliente::create([
                'id_usuario' => $usuario->id_usuario,
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'dni_ruc' => $data['dni_ruc'], // ✅ Ya no es opcional
            ]);

            // 3. Retornar con el formato estándar
            return $this->obtenerDetalleUsuario($usuario->id_usuario);
        });
    }

    /**
     * ✅ NUEVO: Creación por Admin (Técnicos u otros Admins)
     */
    public function crearUsuario(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Obtener el rol por nombre (más seguro que por ID)
            $rol = \App\Models\Rol::where('nombre', $this->obtenerNombreRol($data['id_rol']))->firstOrFail();

            $usuario = Usuario::create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'id_rol' => $data['id_rol'],
            ]);

            // Crear perfil según el nombre del rol
            switch ($rol->nombre) {
                case 'TECNICO':
                    PerfilTecnico::create([
                        'id_usuario' => $usuario->id_usuario,
                        'dni' => $data['dni'],
                        'especialidad' => $data['especialidad'] ?? 'General',
                    ]);
                    break;
                case 'ADMINISTRADOR':
                    PerfilAdmin::create([
                        'id_usuario' => $usuario->id_usuario,
                    ]);
                    break;
                case 'CLIENTE':
                    PerfilCliente::create([
                        'id_usuario' => $usuario->id_usuario,
                        'telefono' => $data['telefono'] ?? null,
                        'direccion' => $data['direccion'] ?? null,
                        'dni_ruc' => $data['dni_ruc'],
                    ]);
                    break;
            }   

            return $this->obtenerDetalleUsuario($usuario->id_usuario);
        });
    }

    private function obtenerNombreRol(int $idRol): string
    {
        $roles = [
            1 => 'ADMINISTRADOR',
            2 => 'TECNICO',
            3 => 'CLIENTE',
        ];

        return $roles[$idRol] ?? throw new \Exception("Rol con ID {$idRol} no existe.");
    }

    /**
     * ✅ NUEVO: Actualizar Mi Perfil (Solo datos personales, no rol)
     */
    public function actualizarPerfil(Usuario $usuario, array $data): array
    {
        return DB::transaction(function () use ($usuario, $data) {
            // 1. Actualizar datos básicos del usuario
            $updateData = [];
            if (isset($data['nombres'])) $updateData['nombres'] = $data['nombres'];
            if (isset($data['apellidos'])) $updateData['apellidos'] = $data['apellidos'];
            if (isset($data['password'])) $updateData['password_hash'] = Hash::make($data['password']);

            if (!empty($updateData)) {
                $usuario->update($updateData);
            }

            // 2. Actualizar perfil específico
            if ($usuario->perfilCliente) {
                $usuario->perfilCliente->update([
                    'telefono' => $data['telefono'] ?? $usuario->perfilCliente->telefono,
                    'direccion' => $data['direccion'] ?? $usuario->perfilCliente->direccion,
                ]);
            } elseif ($usuario->perfilTecnico) {
                // Actualiza campos específicos del técnico si aplica
                $updateTecnico = [];
                if (isset($data['especialidad'])) $updateTecnico['especialidad'] = $data['especialidad'];
                if (!empty($updateTecnico)) {
                    $usuario->perfilTecnico->update($updateTecnico);
                }
            }

            // 3. Retornar con formato estándar
            return $this->obtenerDetalleUsuario($usuario->id_usuario);
        });
    }

    /**
     * ✅ NUEVO: Actualizar Usuario (Solo Admin - puede cambiar rol)
     */
    public function actualizarUsuarioAdmin(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $usuario = Usuario::findOrFail($id);

            // Actualizar datos básicos
            $updateData = [];
            if (isset($data['nombres'])) $updateData['nombres'] = $data['nombres'];
            if (isset($data['apellidos'])) $updateData['apellidos'] = $data['apellidos'];
            if (isset($data['password'])) $updateData['password_hash'] = Hash::make($data['password']);
            if (isset($data['id_rol'])) $updateData['id_rol'] = $data['id_rol'];

            if (!empty($updateData)) {
                $usuario->update($updateData);
            }

            // Si cambió de rol, crear nuevo perfil (lógica avanzada opcional)
            // Por ahora solo actualizamos datos básicos

            return $this->obtenerDetalleUsuario($usuario->id_usuario);
        });
    }

    /**
     * ✅ NUEVO: Listar Usuarios (Solo Admin)
     */
    public function listarUsuarios(): array
    {
        $usuarios = Usuario::with(['rol', 'perfilCliente', 'perfilTecnico', 'perfilAdmin'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $usuarios->map(function ($usuario) {
            return $this->obtenerDetalleUsuario($usuario->id_usuario);
        })->toArray();
    }

    /**
     * ✅ NUEVO: Eliminar Usuario (SoftDelete - Solo Admin)
     */
    public function eliminarUsuario(int $id): void
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete(); // ✅ Usa el método del trait SoftDeletes directamente
    }
    
/**
 * Listar solo técnicos con información específica y carga de trabajo
 * GET /api/usuarios/tecnicos
 */
public function listarTecnicos(): array
{
    $tecnicos = Usuario::whereHas('rol', function ($q) {
        $q->where('nombre', 'TECNICO');
    })
    ->with(['perfilTecnico'])
    ->get()
    ->map(function ($usuario) {
        $idTecnico = $usuario->perfilTecnico?->id_tecnico;
        
        // ✅ NUEVO: Calcular solicitudes activas del técnico
        $solicitudesPendientes = \App\Models\Solicitud::where('id_tecnico', $idTecnico)
            ->whereIn('estado', ['ASIGNADA', 'COTIZADA', 'APROBADA', 'EN_PROCESO'])
            ->count();

        // ✅ NUEVO: Contar urgentes
        $solicitudesUrgentes = \App\Models\Solicitud::where('id_tecnico', $idTecnico)
            ->whereIn('estado', ['ASIGNADA', 'COTIZADA', 'APROBADA', 'EN_PROCESO'])
            ->where('es_urgente', true)
            ->count();

        // ✅ NUEVO: Calcular nivel de carga
        $nivelCarga = match(true) {
            $solicitudesPendientes === 0 => 'LIBRE',
            $solicitudesPendientes <= 2 => 'BAJA',
            $solicitudesPendientes <= 4 => 'MEDIA',
            default => 'ALTA',
        };

        return [
            'id_usuario' => $usuario->id_usuario,
            'id_tecnico' => $idTecnico,
            'nombres' => $usuario->nombres,
            'apellidos' => $usuario->apellidos,
            'nombre_completo' => trim($usuario->nombres . ' ' . $usuario->apellidos),
            'email' => $usuario->email,
            'telefono' => $usuario->perfilTecnico?->telefono,
            'especialidad' => $usuario->perfilTecnico?->especialidad,
            'dni' => $usuario->perfilTecnico?->dni,
            // ✅ NUEVOS CAMPOS
            'solicitudes_pendientes' => $solicitudesPendientes,
            'solicitudes_urgentes' => $solicitudesUrgentes,
            'nivel_carga' => $nivelCarga,
        ];
    })
    // ✅ NUEVO: Ordenar por nivel de carga (LIBRE primero)
    ->sortBy(function ($tecnico) {
        $orden = ['LIBRE' => 0, 'BAJA' => 1, 'MEDIA' => 2, 'ALTA' => 3];
        return $orden[$tecnico['nivel_carga']] ?? 4;
    })
    ->values();

    return [
        'total' => $tecnicos->count(),
        'tecnicos' => $tecnicos->toArray(),
    ];
}

/**
 * ✅ NUEVO: Obtener carga de trabajo de un técnico
 */
public function obtenerCargaTrabajoTecnico(int $idTecnico): array
{
    // Contar solicitudes activas asignadas
    $solicitudesPendientes = \App\Models\Solicitud::where('id_tecnico', $idTecnico)
        ->whereIn('estado', ['ASIGNADA', 'COTIZADA', 'APROBADA', 'EN_PROCESO'])
        ->count();

    // Contar solicitudes urgentes
    $solicitudesUrgentes = \App\Models\Solicitud::where('id_tecnico', $idTecnico)
        ->whereIn('estado', ['ASIGNADA', 'COTIZADA', 'APROBADA', 'EN_PROCESO'])
        ->where('es_urgente', true)
        ->count();

    // Obtener detalle de solicitudes activas
    $solicitudesActivas = \App\Models\Solicitud::where('id_tecnico', $idTecnico)
        ->whereIn('estado', ['ASIGNADA', 'COTIZADA', 'APROBADA', 'EN_PROCESO'])
        ->select('uuid_solicitud', 'estado', 'es_urgente', 'descripcion_problema', 'direccion_servicio', 'fecha_coordinada')
        ->orderBy('es_urgente', 'desc')
        ->orderBy('created_at', 'asc')
        ->get();

    return [
        'id_tecnico' => $idTecnico,
        'solicitudes_pendientes' => $solicitudesPendientes,
        'solicitudes_urgentes' => $solicitudesUrgentes,
        'nivel_carga' => $this->calcularNivelCarga($solicitudesPendientes),
        'detalle_solicitudes' => $solicitudesActivas,
    ];
}

/**
 * Helper para calcular nivel de carga
 */
private function calcularNivelCarga(int $pendientes): string
{
    if ($pendientes === 0) return 'LIBRE';
    if ($pendientes <= 2) return 'BAJA';
    if ($pendientes <= 4) return 'MEDIA';
    return 'ALTA';
}
}
