<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class QrService
{
    private string $key;
    private const CIPHER = 'aes-256-cbc';

    public function __construct()
    {
        $hexKey = env('QR_SECRET_KEY', '');

        if (strlen($hexKey) !== 64) {
            throw new RuntimeException(
                'QR_SECRET_KEY debe ser una cadena hexadecimal de exactamente 64 caracteres (32 bytes).'
            );
        }

        $this->key = hex2bin($hexKey);
    }

    /**
     * Encripta el payload con AES-256-CBC.
     * Devuelve base64(iv) . ':' . base64(ciphertext)
     */
    public function encrypt(int $userId, int $version = 1): string
    {
        $payload = json_encode(['user_id' => $userId, 'v' => $version]);
        $iv = random_bytes(16);

        $ciphertext = openssl_encrypt(
            $payload,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Error al encriptar el payload QR.');
        }

        return base64_encode($iv) . ':' . base64_encode($ciphertext);
    }

    /**
     * Genera una imagen PNG del QR a partir del ID del usuario.
     * Usa GD (nativo) para dibujar la matriz obtenida con BaconQrCode.
     * Devuelve 'data:image/png;base64,...'
     */
    public function generateQrImage(int $userId, int $version = 1): string
    {
        $encryptedText = $this->encrypt($userId, $version);

        // 1. Obtener la matriz de módulos del QR
        $qrCode = Encoder::encode(
            $encryptedText,
            ErrorCorrectionLevel::M(),
            'UTF-8'
        );
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth(); // Número de módulos por lado

        // 2. Configurar tamaño de la imagen y margen
        $imageSize = 500;          // Tamaño deseado en píxeles
        $marginModules = 1;        // Módulos de margen (quiet zone)
        $totalModules = $moduleCount + 2 * $marginModules;
        // Calcular tamaño de módulo en píxeles y forzar entero
        $modulePixel = (int) floor($imageSize / $totalModules);
        // Calcular tamaño real de la imagen (entero)
        $imageSizeAdjusted = (int) ($modulePixel * $totalModules);

        // 3. Crear imagen GD con dimensiones enteras
        $img = imagecreatetruecolor($imageSizeAdjusted, $imageSizeAdjusted);
        // Colores
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        // Rellenar fondo blanco
        imagefill($img, 0, 0, $white);

        // 4. Dibujar los módulos (matriz QR)
        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y)) {
                    $pixelX = (int) (($x + $marginModules) * $modulePixel);
                    $pixelY = (int) (($y + $marginModules) * $modulePixel);
                    imagefilledrectangle(
                        $img,
                        $pixelX,
                        $pixelY,
                        (int) ($pixelX + $modulePixel - 1),
                        (int) ($pixelY + $modulePixel - 1),
                        $black
                    );
                }
            }
        }

        // 5. Obtener la imagen PNG como string
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        // 6. Codificar a base64 y retornar data URI
        return 'data:image/png;base64,' . base64_encode($pngData);
    }
}