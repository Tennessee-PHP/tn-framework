<?php

namespace TN\TN_Core\Service;

/**
 * Service for handling encryption and decryption of sensitive data
 */
class Encryption
{
    private static ?Encryption $instance = null;
    private string $key;
    private string $cipher = 'aes-256-gcm';

    private const int ITERATIONS = 100000; // High iteration count for security
    private const int KEY_LENGTH = 32; // 256 bits for AES-256

    private function __construct()
    {
        if (empty($_ENV['ENCRYPTION_KEY'])) {
            throw new \RuntimeException('ENCRYPTION_KEY must be set in environment variables');
        }

        if (empty($_ENV['ENCRYPTION_SALT'])) {
            throw new \RuntimeException('ENCRYPTION_SALT must be set in environment variables');
        }

        // Use PBKDF2 to derive a secure encryption key
        $this->key = hash_pbkdf2(
            'sha256',                  // Hash algorithm
            $_ENV['ENCRYPTION_KEY'],   // Master key from environment
            $_ENV['ENCRYPTION_SALT'],  // Salt from environment
            self::ITERATIONS,          // Iterations
            self::KEY_LENGTH,          // Key length in bytes
            true                       // Raw binary output
        );
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Encrypt data
     * @param mixed $data Data to encrypt
     * @return string|null Encrypted data or null if encryption fails
     */
    public function encrypt(mixed $data): ?string
    {
        if ($data === null) {
            return null;
        }

        // Convert data to JSON if it's not a string
        $value = is_string($data) ? $data : json_encode($data);

        $iv = random_bytes(12); // GCM recommends 12 bytes

        try {
            $encrypted = openssl_encrypt(
                $value,
                $this->cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($encrypted === false) {
                return null;
            }

            // Combine IV, encrypted data, and tag
            return base64_encode($iv . $tag . $encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Decrypt data
     * @param string|null $data Encrypted data
     * @return mixed Decrypted data or null if decryption fails
     */
    public function decrypt(?string $data): mixed
    {
        if ($data === null) {
            return null;
        }

        try {
            $decoded = base64_decode($data);

            // Extract IV, tag and encrypted data
            $iv = substr($decoded, 0, 12);
            $tag = substr($decoded, 12, 16);
            $encrypted = substr($decoded, 28);

            $decrypted = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $this->key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($decrypted === false) {
                return null;
            }

            // Try to decode JSON if the decrypted data is a JSON string
            $decoded = json_decode($decrypted, true);
            return $decoded !== null ? $decoded : $decrypted;
        } catch (\Exception $e) {
            return null;
        }
    }
}
