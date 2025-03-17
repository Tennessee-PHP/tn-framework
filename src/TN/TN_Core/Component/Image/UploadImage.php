<?php

namespace TN\TN_Core\Component\Image;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;

class UploadImage extends JSON {

    protected const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    protected const MAX_FILE_SIZE = 5242880; // 5MB
    protected const UPLOAD_DIR = 'uploads/images/';

    public function prepare(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header("Access-Control-Allow-Methods: POST, OPTIONS");
            return;
        }

        // tinymce says we need these?
        header('Access-Control-Allow-Credentials: true');
        header('P3P: CP="There is no P3P policy."');

        $file = array_shift($_FILES);
        $this->validateFile($file);

        try {
            $url = $this->saveFile($file);
        } catch (\Exception $e) {
            // tinymce wants text error messages
            http_response_code(400);
            echo $e->getMessage();
            exit;
        }

        $this->data = [
            'location' => $url
        ];
    }

    protected function validateFile(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('No file uploaded');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('Upload failed with error code: ' . $file['error']);
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new ValidationException('File size exceeds maximum allowed size of 5MB');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new ValidationException('Invalid file type. Allowed types: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }
    }

    protected function saveFile(array $file): string
    {
        if (!file_exists(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0777, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $extension;
        $filepath = self::UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new ValidationException('Failed to save uploaded file');
        }

        return '/' . $filepath;
    }
}