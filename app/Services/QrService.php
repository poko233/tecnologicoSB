<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Writer;

class QrService
{
    private string $key;
    private const CIPHER = 'aes-256-cbc';

    public function __construct()
    {
        $hexKey = config('qr.secret_key');

        if (strlen($hexKey) !== 64) {
            throw new RuntimeException(
                'QR_SECRET_KEY debe ser una cadena hexadecimal de exactamente 64 caracteres (32 bytes).'
            );
        }

        $this->key = hex2bin($hexKey);
    }

    /**
     * Encripta el payload con AES-256-CBC.
     * Devuelve una cadena en Base64URL (sin padding) que
     * contiene la concatenación IV (16 bytes) + ciphertext.
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

        $combined = $iv . $ciphertext;

        // Base64URL: reemplazar '+' por '-', '/' por '_', y quitar '='
        return rtrim(strtr(base64_encode($combined), '+/', '-_'), '=');
    }

    /**
     * Genera una imagen PNG del QR.
     */
    public function generateQrImage(int $userId, int $version = 1): string
    {
        $encryptedText = $this->encrypt($userId, $version);

        $qrCode = Encoder::encode(
            $encryptedText,
            ErrorCorrectionLevel::M(),
            'UTF-8'
        );
        $matrix = $qrCode->getMatrix();
        $moduleCount = $matrix->getWidth();

        $imageSize = 500;
        $marginModules = 1;
        $totalModules = $moduleCount + 2 * $marginModules;
        $modulePixel = (int) floor($imageSize / $totalModules);
        $imageSizeAdjusted = (int) ($modulePixel * $totalModules);

        $img = imagecreatetruecolor($imageSizeAdjusted, $imageSizeAdjusted);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

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

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($pngData);
    }
}