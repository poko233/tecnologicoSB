<?php

namespace App\Services;

use RuntimeException;

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

        // Convertir hex → binario (32 bytes reales para AES-256)
        $this->key = hex2bin($hexKey);
    }

    /**
     * Encripta el payload con AES-256-CBC.
     * Devuelve base64(iv) . ':' . base64(ciphertext)
     */
    public function encrypt(int $userId, int $version = 1): string
    {
        $payload = json_encode(['user_id' => $userId, 'v' => $version]);
        $iv      = random_bytes(16);

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
     * Encripta el payload QR y genera una imagen QR en formato data URI (base64).
     * Devuelve 'data:image/svg+xml;base64,' . base64(svg_content)
     */
    public function generateQrImage(int $userId, int $version = 1): string
    {
        $encryptedText = $this->encrypt($userId, $version);

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $svgData = $writer->writeString($encryptedText);

        return 'data:image/svg+xml;base64,' . base64_encode($svgData);
    }

    /**
     * Desencripta un string QR.
     * Devuelve el array del payload o null si falla.
     */
    public function decrypt(string $qrData): ?array
    {
        $parts = explode(':', $qrData, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$ivBase64, $ciphertextBase64] = $parts;

        $iv         = base64_decode($ivBase64, true);
        $ciphertext = base64_decode($ciphertextBase64, true);

        if ($iv === false || $ciphertext === false) {
            return null;
        }

        $payload = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($payload === false) {
            return null;
        }

        $data = json_decode($payload, true);

        // Validar estructura mínima
        if (!is_array($data) || !isset($data['user_id'])) {
            return null;
        }

        return $data;
    }
}
