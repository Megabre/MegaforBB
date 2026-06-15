<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Image upload for Toast UI Editor, CKEditor and other editors.
 */
class UploadController extends BaseController
{
    /** İzin verilen MIME tipleri */
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /** Maksimum dosya boyutu (byte) */
    private const MAX_SIZE = 5 * 1024 * 1024; // 5 MB

    /**
     * POST /upload/image — Toast UI Editor (addImageBlobHook) ve diğer editörler.
     * Yanıt: JSON { "url": "/path/to/image.jpg" } veya hata.
     */
    public function image(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->app->auth()->user() === null) {
            http_response_code(403);
            echo json_encode(['error' => ['message' => lang('upload.login_required')]]);
            exit;
        }

        $file = $_FILES['upload'] ?? $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => lang('upload.file_upload_failed')]]);
            exit;
        }

        $tmpPath = $file['tmp_name'];
        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => lang('upload.file_too_large_5mb')]]);
            exit;
        }

        $mime = $this->detectMimeType($tmpPath);
        if (!isset(self::ALLOWED_TYPES[$mime])) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => lang('upload.invalid_file_type')]]);
            exit;
        }

        $toUpload = $tmpPath;
        $ext = self::ALLOWED_TYPES[$mime];
        if (\App\Services\ImageProcessor::isProcessableImage($mime)) {
            $processed = \App\Services\ImageProcessor::processToWebp($tmpPath);
            if ($processed !== null) {
                $toUpload = $processed;
                $ext = 'webp';
            }
        }
        $subDir = date('Y') . '/' . date('m');
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $relative = 'uploads/images/' . $subDir . '/' . $name;
        $storage = $this->storage();
        if (!$storage->putFile($relative, $toUpload)) {
            http_response_code(500);
            echo json_encode(['error' => ['message' => lang('upload.file_save_failed')]]);
            exit;
        }

        if ($toUpload !== $tmpPath && is_file($toUpload)) {
            @unlink($toUpload);
        }
        $url = $storage->url($relative);
        echo json_encode(['url' => $url]);
        exit;
    }

    private function detectMimeType(string $tmpPath): ?string
    {
        if (function_exists('finfo_open') && function_exists('finfo_file') && defined('FILEINFO_MIME_TYPE')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = @finfo_file($finfo, $tmpPath);
                @finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmpPath);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        if (function_exists('getimagesize')) {
            $info = @getimagesize($tmpPath);
            if (is_array($info) && isset($info['mime']) && is_string($info['mime']) && $info['mime'] !== '') {
                return $info['mime'];
            }
        }

        return null;
    }
}
