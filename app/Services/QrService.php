<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

class QrService
{
    private string $key;
    private const CIPHER = 'aes-256-cbc';

    public function __construct()
    {
        $hexKey = config('qr.secret_key');
        if (strlen($hexKey) !== 64) {
            throw new RuntimeException('QR_SECRET_KEY debe ser 64 caracteres hex (32 bytes).');
        }
        $this->key = hex2bin($hexKey);
    }

    /**
     * Encripta el payload y devuelve el ciphertext codificado en Base62.
     * IV fijo = primeros 16 bytes de la clave (suficiente para este caso de uso).
     */
    public function encrypt(int $userId, int $version = 1): string
    {
        $payload = json_encode(['user_id' => $userId, 'v' => $version]);

        // IV fijo: primeros 16 bytes de la propia clave
        $iv = substr($this->key, 0, 16);

        $ciphertext = openssl_encrypt(
            $payload,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Error al encriptar.');
        }

        // Codificar solo el ciphertext (sin IV) en Base62
        return self::base62Encode($ciphertext);
    }

    /**
     * Genera imagen QR del texto encriptado.
     */
    public function generateQrImage(int $userId, int $version = 1): string
    {
        $encryptedText = $this->encrypt($userId, $version);

        $qrCode = Encoder::encode($encryptedText, ErrorCorrectionLevel::M(), 'UTF-8');
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
    private static function base62Encode(string $data): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $base = 62;

        // Convertir binario a array de dígitos decimales (big number)
        $decimal = '0';
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($data[$i]);
            $decimal = self::bigAdd(self::bigMul($decimal, '256'), (string) $byte);
        }

        // Convertir decimal a Base62
        if ($decimal === '0') {
            return '0';
        }

        $encoded = '';
        while (bccomp($decimal, '0') > 0) {
            $divResult = self::bigDivMod($decimal, $base);
            $encoded = $chars[$divResult['mod']] . $encoded;
            $decimal = $divResult['div'];
        }

        return $encoded;
    }

    // Funciones auxiliares para aritmética con strings (big numbers)
    private static function bigAdd(string $a, string $b): string
    {
        $len = max(strlen($a), strlen($b));
        $a = str_pad($a, $len, '0', STR_PAD_LEFT);
        $b = str_pad($b, $len, '0', STR_PAD_LEFT);
        $carry = 0;
        $result = '';
        for ($i = $len - 1; $i >= 0; $i--) {
            $sum = (int) $a[$i] + (int) $b[$i] + $carry;
            $carry = (int) ($sum / 10);
            $result = ($sum % 10) . $result;
        }
        if ($carry > 0) {
            $result = $carry . $result;
        }
        return $result;
    }

    private static function bigMul(string $a, string $b): string
    {
        // Multiplicación simple para números no muy grandes (máx 32 bytes → 77 dígitos)
        $result = '0';
        $lenB = strlen($b);
        for ($i = $lenB - 1; $i >= 0; $i--) {
            $digit = (int) $b[$i];
            $temp = self::bigMulDigit($a, $digit);
            $temp = str_pad($temp, strlen($temp) + ($lenB - 1 - $i), '0', STR_PAD_RIGHT);
            $result = self::bigAdd($result, $temp);
        }
        return $result;
    }

    private static function bigMulDigit(string $a, int $digit): string
    {
        if ($digit === 0)
            return '0';
        $carry = 0;
        $result = '';
        for ($i = strlen($a) - 1; $i >= 0; $i--) {
            $prod = (int) $a[$i] * $digit + $carry;
            $carry = (int) ($prod / 10);
            $result = ($prod % 10) . $result;
        }
        if ($carry > 0) {
            $result = $carry . $result;
        }
        return $result;
    }

    private static function bigDivMod(string $a, int $divisor): array
    {
        $quotient = '';
        $remainder = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $remainder = $remainder * 10 + (int) $a[$i];
            $quotient .= (int) ($remainder / $divisor);
            $remainder = $remainder % $divisor;
        }
        $quotient = ltrim($quotient, '0') ?: '0';
        return ['div' => $quotient, 'mod' => $remainder];
    }
}