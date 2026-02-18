<?php

namespace TN\TN_Core\Service;

class Encryption
{
    private static ?Encryption $instance = null;
    private string $key;
    private string $cipher = 'AES-256-CBC';

    private function __construct()
    {
        $this->key = $_ENV['ENCRYPTION_KEY'] ?? $this->getDefaultKey();
    }

    public static function getInstance(): Encryption
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getDefaultKey(): string
    {
        // Fallback to a default key if ENCRYPTION_KEY is not set
        // In production, ENCRYPTION_KEY should always be set in environment
        return hash('sha256', 'default-encryption-key-change-in-production', true);
    }

    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $data): string
    {
        $data = base64_decode($data, true);
        if ($data === false) {
            return '';
        }
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv) ?: '';
    }
}

