<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Attachment;

/**
 * Dosya ekleri: yükleme, indirme, silme.
 */
class AttachmentController extends BaseController
{
    private const MAX_SIZE = 10 * 1024 * 1024; // 10 MB

    private const ALLOWED_EXTENSIONS = [
        'pdf', 'zip', 'rar', '7z', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mp3',
    ];

    private const EXT_TO_MIME = [
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'mp3' => 'audio/mpeg',
    ];

    /**
     * POST /upload/attachment — AJAX dosya yükleme.
     * Yanıt: JSON { id, original_name, file_size, download_url } veya hata.
     */
    public function upload(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid('upload_attachment', (string) ($_POST['_token'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['error' => lang('upload.invalid_request')]);
            exit;
        }

        $user = $this->app->auth()->user();
        if (!$user) {
            http_response_code(403);
            echo json_encode(['error' => lang('upload.login_required')]);
            exit;
        }

        $file = $_FILES['attachment'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => lang('upload.file_upload_failed')]);
            exit;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => lang('upload.file_too_large_10mb')]);
            exit;
        }

        $originalName = basename((string) ($file['name'] ?? ''));
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            http_response_code(400);
            echo json_encode(['error' => lang('upload.invalid_file_type_allowed', ['list' => implode(', ', self::ALLOWED_EXTENSIONS)])]);
            exit;
        }

        $expectedMime = self::EXT_TO_MIME[$ext] ?? null;
        $detectedMime = $this->detectMimeType((string) $file['tmp_name']);
        if ($detectedMime === null && $expectedMime !== null) {
            $detectedMime = $expectedMime;
        }
        $acceptZip = in_array($ext, ['docx', 'xlsx', 'pptx'], true);
        if ($expectedMime && $detectedMime !== $expectedMime && !($acceptZip && $detectedMime === 'application/zip')) {
            http_response_code(400);
            echo json_encode(['error' => lang('upload.file_type_mismatch')]);
            exit;
        }

        $mimeType = $detectedMime ?: (self::EXT_TO_MIME[$ext] ?? 'application/octet-stream');
        $toUpload = $file['tmp_name'];
        if (\App\Services\ImageProcessor::isProcessableImage($mimeType)) {
            $processed = \App\Services\ImageProcessor::processToWebp($file['tmp_name']);
            if ($processed !== null) {
                $toUpload = $processed;
                $ext = 'webp';
                $mimeType = 'image/webp';
                $size = (int) @filesize($processed);
            }
        }
        // Kullanıcı dosya adı path'e eklenmez (path traversal / çift uzantı riski). Sadece uniqid + whitelist uzantı.
        $storedName = uniqid('att_', true) . '.' . $ext;
        $storage = $this->storage();
        $relativePath = 'uploads/attachments/' . $storedName;
        if (!$storage->putFile($relativePath, $toUpload)) {
            http_response_code(500);
            echo json_encode(['error' => lang('upload.file_save_failed')]);
            exit;
        }
        if ($toUpload !== $file['tmp_name'] && is_file($toUpload)) {
            @unlink($toUpload);
        }

        $storageDriver = $storage->getDriver();
        $att = Attachment::create([
            'post_id' => null,
            'user_id' => $user->id,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'storage_driver' => $storageDriver,
            'mime_type' => $mimeType,
            'file_size' => $size,
        ]);
        if (function_exists('sef_service') && sef_service()->getMode() === 'random') {
            $att->url_key = sef_service()->generateUniqueUrlKeyForTable('attachments', 'url_key');
            $att->save();
        }
        $id = $att->id;

        $downloadUrl = core_url('attachment/' . attachment_url_path($att) . '/download');
        echo json_encode([
            'id' => $id,
            'original_name' => $originalName,
            'file_size' => $size,
            'download_url' => $downloadUrl,
        ]);
        exit;
    }

    /**
     * GET /attachment/{id}/download — Dosyayı indir.
     */
    public function download(string $id): void
    {
        $attId = resolve_attachment_id($id);
        if ($attId === null) {
            http_response_code(404);
            echo lang('upload.attachment_not_found_plain');
            exit;
        }
        $att = Attachment::find($attId, ['id', 'original_name', 'stored_name', 'storage_driver', 'mime_type', 'file_size']);
        if (!$att) {
            http_response_code(404);
            echo lang('upload.attachment_not_found_plain');
            exit;
        }

        $storage = $this->storage();
        $relativePath = 'uploads/attachments/' . $att->stored_name;
        $driver = $att->storage_driver ?? 'local';

        if (in_array($driver, ['aws_s3', 'r2', 's3'], true)) {
            $att->increment('download_count');
            $url = $storage->url($relativePath, $driver);
            header('Location: ' . $url, true, 302);
            exit;
        }

        $contents = $storage->get($relativePath);
        if ($contents === null) {
            http_response_code(404);
            echo lang('upload.file_not_found');
            exit;
        }

        $att->increment('download_count');

        $safeName = preg_replace('/[^\w.-]/', '_', $att->original_name);
        header('Content-Type: ' . ($att->mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . strlen($contents));
        echo $contents;
        exit;
    }

    /**
     * POST /attachment/{id}/delete — Delete (owner or admin/moderator).
     */
    public function delete(string $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid('attachment_delete', (string)($_POST['_token'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['error' => lang('upload.invalid_request')]);
            exit;
        }

        $user = $this->app->auth()->user();
        if (!$user) {
            http_response_code(403);
            echo json_encode(['error' => lang('upload.login_required')]);
            exit;
        }

        $attId = resolve_attachment_id($id);
        if ($attId === null) {
            http_response_code(404);
            echo json_encode(['error' => lang('upload.attachment_not_found')]);
            exit;
        }
        $att = Attachment::find($attId, ['id', 'user_id', 'stored_name', 'storage_driver']);
        if (!$att) {
            http_response_code(404);
            echo json_encode(['error' => lang('upload.attachment_not_found')]);
            exit;
        }

        $rid = (int) ($user->role_id ?? 0);
        $isStaff = ($rid === 1 || $rid === 2);
        $isOwner = ((int) $att->user_id) === ((int) $user->id);
        if (!$isOwner && !$isStaff) {
            http_response_code(403);
            echo json_encode(['error' => lang('upload.no_permission_delete')]);
            exit;
        }

        $relativePath = 'uploads/attachments/' . $att->stored_name;
        $storage = $this->storage();
        $attDriver = $att->storage_driver ?? 'local';
        if (in_array($attDriver, ['aws_s3', 'r2', 's3'], true)) {
            $storage->delete($relativePath, $attDriver);
        } else {
            $storage->delete($relativePath);
        }

        $att->delete();

        echo json_encode(['success' => true]);
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

        return null;
    }
}
