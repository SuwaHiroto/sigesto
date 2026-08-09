<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudinaryService
{
    private string $cloudName;
    private string $uploadPreset;
    private string $apiKey;
    private string $apiSecret;

    private array $tiposPermitidos = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf'
    ];

    private int $tamanoMaximo = 5 * 1024 * 1024; // 5MB

    public function __construct()
    {
        $this->cloudName = env('CLOUDINARY_CLOUD_NAME');
        $this->uploadPreset = env('CLOUDINARY_UPLOAD_PRESET');
        $this->apiKey = env('CLOUDINARY_API_KEY');
        $this->apiSecret = env('CLOUDINARY_API_SECRET');

        if (empty($this->cloudName) || empty($this->uploadPreset)) {
            throw new Exception('Configuración de Cloudinary incompleta en .env');
        }
    }

    /**
     * ✅ Subir archivo a Cloudinary (genérico)
     * @param UploadedFile $archivo
     * @param string $carpeta Carpeta destino (ej: 'sigesto/evidencias', 'sigesto/pagos')
     * @return array ['url' => ..., 'public_id' => ...]
     */
    public function subir(UploadedFile $archivo, string $carpeta = 'sigesto/general', int $intentos = 2): array
    {
        $this->validarArchivo($archivo);

        $url = "https://api.cloudinary.com/v1_1/{$this->cloudName}/auto/upload";
        $params = [
            'file' => new \CURLFile(
                $archivo->getRealPath(),
                $archivo->getMimeType(),
                $archivo->getClientOriginalName()
            ),
            'upload_preset' => $this->uploadPreset,
            'folder' => $carpeta,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error && $intentos > 0) {
            sleep(1);
            return $this->subir($archivo, $carpeta, $intentos - 1);
        }

        if ($error) {
            throw new Exception("Error de red al subir a Cloudinary: {$error}");
        }

        if ($httpCode !== 200) {
            throw new Exception("Cloudinary respondió con HTTP {$httpCode}: {$respuesta}");
        }

        $data = json_decode($respuesta, true);

        if (json_last_error() !== JSON_ERROR_NONE || isset($data['error'])) {
            $msg = $data['error']['message'] ?? 'Respuesta inválida de Cloudinary.';
            throw new Exception("Cloudinary rechazó el archivo: {$msg}");
        }

        if (empty($data['secure_url']) || empty($data['public_id'])) {
            throw new Exception('Respuesta incompleta de Cloudinary.');
        }

        return [
            'url' => $data['secure_url'],
            'public_id' => $data['public_id'],
        ];
    }

    /**
     * ✅ Eliminar archivo de Cloudinary
     */
    public function eliminar(string $urlArchivo): bool
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return false;
        }

        $publicId = $this->extraerPublicId($urlArchivo);
        if (!$publicId) {
            return false;
        }

        try {
            $timestamp = time();
            $signature = sha1("public_id={$publicId}&timestamp={$timestamp}" . $this->apiSecret);

            $ch = curl_init("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'public_id' => $publicId,
                'signature' => $signature,
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
            ]);
            curl_exec($ch);
            curl_close($ch);

            return true;
        } catch (Exception $e) {
            Log::warning('No se pudo eliminar de Cloudinary: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar archivo antes de subir
     */
    private function validarArchivo(UploadedFile $archivo): void
    {
        if ($archivo->getSize() > $this->tamanoMaximo) {
            throw new Exception("El archivo excede el tamaño máximo de 5MB.");
        }

        $mimeType = $archivo->getMimeType();
        if (!in_array($mimeType, $this->tiposPermitidos)) {
            throw new Exception("Tipo de archivo no permitido: {$mimeType}");
        }

        if (str_starts_with($mimeType, 'image/')) {
            $info = getimagesize($archivo->getRealPath());
            if ($info === false) {
                throw new Exception('El archivo no es una imagen válida.');
            }
        }
    }

    /**
     * Extraer public_id de la URL de Cloudinary
     */
    private function extraerPublicId(string $url): string
    {
        if (preg_match('/res\.cloudinary\.com\/[^\/]+\/[^\/]+\/upload\/v\d+\/(.+)\.[a-z]+$/i', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
