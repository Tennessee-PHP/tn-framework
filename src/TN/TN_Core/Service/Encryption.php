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
        // #region agent log
        file_put_contents('/Users/simonshepherd/Footballguys/fbgsite/.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Encryption.php:encrypt', 'message' => 'Encrypting data', 'data' => ['dataLength' => strlen($data), 'cipher' => $this->cipher], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
        // #endregion
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $result = base64_encode($iv . $encrypted);
        // #region agent log
        file_put_contents('/Users/simonshepherd/Footballguys/fbgsite/.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Encryption.php:encrypt', 'message' => 'Encryption successful', 'data' => ['resultLength' => strlen($result)], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    public function decrypt(string $data): string
    {
        // #region agent log
        file_put_contents('/Users/simonshepherd/Footballguys/fbgsite/.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Encryption.php:decrypt', 'message' => 'Decrypting data', 'data' => ['dataLength' => strlen($data)], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
        // #endregion
        $data = base64_decode($data, true);
        if ($data === false) {
            // #region agent log
            file_put_contents('/Users/simonshepherd/Footballguys/fbgsite/.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Encryption.php:decrypt', 'message' => 'Base64 decode failed', 'data' => [], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
            // #endregion
            return '';
        }
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        $result = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv) ?: '';
        // #region agent log
        file_put_contents('/Users/simonshepherd/Footballguys/fbgsite/.cursor/debug.log', json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Encryption.php:decrypt', 'message' => 'Decryption complete', 'data' => ['resultLength' => strlen($result)], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }
}

