<?php

namespace TN\TN_Core\Model\Storage\R2;

use TN\TN_Core\Trait\ExtendedSingletons;
use TN\TN_Core\Trait\Getter;
use TN\TN_Core\Trait\PerformanceRecorder;
use TN\TN_Core\Error\ValidationException;
use Aws\S3\S3Client;

/**
 * Base class for declarative Cloudflare R2 bucket configuration
 */
abstract class R2Bucket
{
    use ExtendedSingletons;
    use Getter;
    use PerformanceRecorder;

    /** @var string Unique identifier for this bucket */
    protected string $key;

    /** @var string The R2 bucket name */
    protected string $bucketName;

    /** @var string Public base URL for direct access (must include trailing slash) */
    protected string $publicBaseUrl;

    /** @var string R2 region (usually 'auto') */
    protected string $region = 'auto';

    /** @var array Default allowed MIME types (can be overridden in subclasses) */
    protected array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

    /** @var int Default maximum file size in bytes (can be overridden in subclasses) */
    protected int $maxFileSize = 2 * 1024 * 1024; // 2MB

    /**
     * Get R2 client for this bucket
     */
    public function getClient(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'endpoint' => $this->getEndpoint(),
            'credentials' => [
                'key' => $this->getAccessKey(),
                'secret' => $this->getSecretKey(),
            ],
            'use_path_style_endpoint' => true,
            'http' => [
                'timeout' => 60,
                'connect_timeout' => 10,
            ],
        ]);
    }

    /**
     * Upload file to this R2 bucket
     */
    public function uploadFile(string $filePath, string $key, ?string $contentType = null): string
    {
        try {
            $event = self::startPerformanceEvent('R2', "PUT {$key}", ['bucket' => $this->bucketName, 'contentType' => $contentType]);

            $client = $this->getClient();

            $client->putObject([
                'Bucket' => $this->bucketName,
                'Key' => $key,
                'SourceFile' => $filePath,
                'ContentType' => $contentType ?? $this->getMimeType($filePath),
            ]);

            $result = $this->getPublicUrl($key);
            $event?->end();
            return $result;
        } catch (\Exception $e) {
            throw new ValidationException('Failed to upload file to R2 storage: ' . $e->getMessage());
        }
    }

    /**
     * Get public base URL for this bucket
     * 
     * @return string Public base URL (includes trailing slash)
     */
    public function getPublicBaseUrl(): string
    {
        return $this->publicBaseUrl;
    }

    /**
     * Get public URL for a file key
     * 
     * @param string $key File key/path (without leading slash)
     * @return string Complete public URL
     */
    public function getPublicUrl(string $key): string
    {
        return "{$this->publicBaseUrl}{$key}";
    }

    /**
     * Get R2 endpoint from environment
     */
    protected function getEndpoint(): string
    {
        return $_ENV['CLOUDFLARE_R2_ENDPOINT'] ?? '';
    }

    /**
     * Get access key from environment
     */
    protected function getAccessKey(): string
    {
        return $_ENV['CLOUDFLARE_R2_ACCESS_KEY_ID'] ?? '';
    }

    /**
     * Get secret key from environment
     */
    protected function getSecretKey(): string
    {
        return $_ENV['CLOUDFLARE_R2_SECRET_ACCESS_KEY'] ?? '';
    }

    /**
     * Upload file with validation and dynamic filename generation
     * 
     * @param string $tempFilePath Temporary file path
     * @param string $mimeType MIME type of file
     * @param int $fileSize File size in bytes
     * @param callable $filenameGenerator Function that returns filename: fn(string $mimeType, array $extraData) => string
     * @param array $extraData Extra data to pass to filename generator
     * @return string Public URL of uploaded file
     * @throws ValidationException If validation fails
     */
    public function uploadWithValidation(
        string $tempFilePath,
        string $mimeType,
        int $fileSize,
        callable $filenameGenerator,
        array $extraData = []
    ): string {
        $this->validateUploadFile($tempFilePath, $mimeType, $fileSize, $this->allowedTypes, $this->maxFileSize);

        $filename = $filenameGenerator($mimeType, $extraData);

        return $this->uploadFile($tempFilePath, $filename);
    }

    /**
     * Validate uploaded file meets requirements
     * 
     * @param string $tempFilePath File path to validate
     * @param string $mimeType MIME type
     * @param int $fileSize File size in bytes
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @throws ValidationException If validation fails
     */
    protected function validateUploadFile(string $tempFilePath, string $mimeType, int $fileSize, array $allowedTypes, int $maxSize): void
    {
        // Validate file type
        if (!in_array($mimeType, $allowedTypes)) {
            $allowedTypesStr = implode(', ', array_map(fn($type) => str_replace('image/', '', $type), $allowedTypes));
            throw new ValidationException("Please upload a {$allowedTypesStr} file");
        }

        // Validate file size
        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 1);
            throw new ValidationException("File size must be less than {$maxSizeMB}MB");
        }

        // Validate file exists
        if (!file_exists($tempFilePath)) {
            throw new ValidationException('Uploaded file not found');
        }
    }

    /**
     * Get file extension from MIME type
     */
    protected function getFileExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/json' => 'json',
            default => 'bin'
        };
    }

    /**
     * Delete file from R2 bucket by URL
     * 
     * @param string $fileUrl Full URL of file to delete
     * @return bool True if deletion succeeded, false otherwise
     */
    public function deleteByUrl(string $fileUrl): bool
    {
        // Check if this URL belongs to our bucket
        if (!str_starts_with($fileUrl, $this->publicBaseUrl)) {
            return false; // Not our URL, don't try to delete
        }

        // Extract the key from the URL
        $key = substr($fileUrl, strlen($this->publicBaseUrl));

        return $this->deleteByKey($key);
    }

    /**
     * Delete file from R2 bucket by key
     * 
     * @param string $key File key/path in bucket
     * @return bool True if deletion succeeded, false otherwise
     */
    public function deleteByKey(string $key): bool
    {
        try {
            $event = self::startPerformanceEvent('R2', "DELETE {$key}", ['bucket' => $this->bucketName]);

            $client = $this->getClient();

            $client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key' => $key
            ]);

            $event?->end();
            return true;
        } catch (\Exception $e) {
            // Log error but don't throw - deletion failure shouldn't break upload
            error_log("Failed to delete R2 object {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a file exists in the R2 bucket
     * 
     * @param string $key File key/path in bucket
     * @return bool True if file exists, false otherwise
     */
    public function fileExists(string $key): bool
    {
        try {
            $event = self::startPerformanceEvent('R2', "HEAD {$key}", ['bucket' => $this->bucketName]);

            $client = $this->getClient();

            $client->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $key
            ]);

            $event?->end();
            return true;
        } catch (\Exception $e) {
            // File doesn't exist or other error occurred
            return false;
        }
    }

    /**
     * Get file size from R2 bucket
     * 
     * @param string $key File key/path in bucket
     * @return int File size in bytes, or 0 if file doesn't exist
     */
    public function getFileSize(string $key): int
    {
        try {
            $event = self::startPerformanceEvent('R2', "HEAD {$key} (size)", ['bucket' => $this->bucketName]);

            $client = $this->getClient();

            $result = $client->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $key
            ]);

            $event?->end();
            return (int) $result['ContentLength'];
        } catch (\Exception $e) {
            // File doesn't exist or other error occurred
            return 0;
        }
    }

    /**
     * Download file contents from R2 bucket by key
     * 
     * @param string $key File key/path in bucket
     * @return string File contents as string
     * @throws ValidationException If file doesn't exist or download fails
     */
    public function downloadFileContents(string $key): string
    {
        try {
            $event = self::startPerformanceEvent('R2', "GET {$key}", ['bucket' => $this->bucketName]);

            $client = $this->getClient();

            $result = $client->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $key
            ]);

            $event?->end();
            return (string) $result['Body'];
        } catch (\Exception $e) {
            throw new ValidationException('Failed to download file from R2 storage: ' . $e->getMessage());
        }
    }

    /**
     * Get MIME type of file
     */
    private function getMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }
}
