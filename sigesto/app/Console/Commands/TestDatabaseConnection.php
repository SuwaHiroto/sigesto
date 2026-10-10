<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Solicitud; // Usamos la entidad mapeada en la Actividad 2

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test-connection';
    protected $description = 'Prueba de integración: Conexión ORM y persistencia';

    public function handle()
    {
        $this->info('Iniciando prueba de conexión...');

        try {
            // 1. Invocar la inicialización de la conexión del ORM
            $pdo = DB::connection()->getPdo();

            // 2. Validar persistencia consultando la entidad mapeada
            $totalRegistros = Solicitud::count();

            // 3. Mensaje de éxito exacto requerido por la actividad
            $this->info('¡Base de datos conectada con éxito vía ORM!');
            $this->info("Registros persistidos en la tabla 'solicitudes': {$totalRegistros}");

        } catch (\Exception $e) {
            // Captura e impresión de errores
            $this->error('Fallo en la conexión: ' . $e->getMessage());
        }

        return 0;
    }
}