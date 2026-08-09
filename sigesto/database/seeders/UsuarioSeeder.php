<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use App\Models\PerfilAdmin;
use App\Models\PerfilTecnico;
use App\Models\PerfilCliente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Roles
        $rolAdmin = Rol::create(['nombre' => 'ADMINISTRADOR']);
        $rolTecnico = Rol::create(['nombre' => 'TECNICO']);
        $rolCliente = Rol::create(['nombre' => 'CLIENTE']);

        // 2. Crear Usuario Admin
        $admin = Usuario::create([
            'id_rol' => $rolAdmin->id_rol,
            'email' => 'admin@sigesto.com',
            'password_hash' => Hash::make('123456'), // Contraseña de prueba
            'nombres' => 'Carlos',
            'apellidos' => 'Administrador',
        ]);
        PerfilAdmin::create(['id_usuario' => $admin->id_usuario]);

        // 3. Crear Usuario Técnico
        $tecnico = Usuario::create([
            'id_rol' => $rolTecnico->id_rol,
            'email' => 'tecnico@sigesto.com',
            'password_hash' => Hash::make('123456'),
            'nombres' => 'Juan',
            'apellidos' => 'Electricista',
        ]);
        PerfilTecnico::create([
            'id_usuario' => $tecnico->id_usuario,
            'dni' => '12345678',
            'especialidad' => 'Electricidad Residencial',
            'disponible' => true,
        ]);

        // 4. Crear Usuario Cliente
        $cliente = Usuario::create([
            'id_rol' => $rolCliente->id_rol,
            'email' => 'cliente@sigesto.com',
            'password_hash' => Hash::make('123456'),
            'nombres' => 'Maria',
            'apellidos' => 'Perez',
        ]);
        PerfilCliente::create([
            'id_usuario' => $cliente->id_usuario,
            'dni_ruc' => '10123456789',
            'telefono' => '987654321',
            'direccion' => 'Av. Sol 123, Cusco',
        ]);
    }
}
