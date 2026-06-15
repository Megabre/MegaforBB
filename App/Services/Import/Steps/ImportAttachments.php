<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Models\Setting;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

/**
 * XenForo attachment import: xf_attachment + xf_attachment_data.
 * Requires option 'xenforo_internal_data_path' = path to XenForo internal_data folder
 * (e.g. /var/www/xenforo/internal_data or C:\xampp\htdocs\xenforo\internal_data).
 * Files are copied to MegaforBB uploads/attachments/.
 */
class ImportAttachments implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_attachments');
    }

    public function key(): string
    {
        return 'attachments';
    }

    public function order(): int
    {
        return 52;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();

        $internalDataPath = isset($options['xenforo_internal_data_path'])
            ? rtrim(str_replace('\\', '/', (string) $options['xenforo_internal_data_path']), '/')
            : '';

        if ($internalDataPath === '' || !is_dir($internalDataPath)) {
            $result->errors = 1;
            $result->errorMessages[] = $internalDataPath === ''
                ? 'XenForo internal_data klasör yolu verilmedi. Bağlantı formunda "XenForo internal_data yolu" alanını doldurun.'
                : "XenForo internal_data klasörü bulunamadı: {$internalDataPath}";
            return $result;
        }

        $mapper->preload('post');
        $mapper->preload('user');

        $uploadsDir = $this->getUploadsAttachmentsDir();
        if ($uploadsDir === null || !is_dir($uploadsDir)) {
            $result->errors = 1;
            $result->errorMessages[] = 'MegaforBB uploads/attachments klasörü yazılamıyor veya bulunamadı.';
            return $result;
        }

        $rows = $sourcePdo->query("
            SELECT a.attachment_id, a.content_id AS post_id, a.data_id, a.attach_date,
                   d.user_id, d.filename, d.file_size, d.file_hash, d.file_path AS data_file_path
            FROM xf_attachment a
            INNER JOIN xf_attachment_data d ON d.data_id = a.data_id
            WHERE a.content_type = 'post' AND a.unassociated = 0
            ORDER BY a.attachment_id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);

        $insert = $targetPdo->prepare("
            INSERT INTO attachments
                (post_id, user_id, original_name, stored_name, storage_driver, mime_type, file_size, download_count, created_at)
            VALUES (?, ?, ?, ?, 'local', ?, ?, 0, ?)
        ");

        foreach ($rows as $row) {
            try {
                $postId = (int) $row['post_id'];
                $newPostId = $mapper->get('post', $postId);
                if ($newPostId === null) {
                    $result->skipped++;
                    continue;
                }

                $userId = (int) $row['user_id'];
                $newUserId = $mapper->get('user', $userId);
                if ($newUserId === null) {
                    $newUserId = 0;
                }

                $dataId = (int) $row['data_id'];
                $fileHash = $row['file_hash'] ?? '';
                $sourcePath = $this->resolveXenForoFilePath(
                    $internalDataPath,
                    $dataId,
                    $row['data_file_path'] ?? '',
                    $fileHash
                );
                if ($sourcePath === null || !is_file($sourcePath)) {
                    $result->errors++;
                    $result->errorMessages[] = "Attachment #{$row['attachment_id']}: dosya bulunamadı (data_id={$dataId})";
                    continue;
                }

                $originalName = !empty($row['filename']) ? $row['filename'] : ('attachment-' . $dataId);
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                if ($ext === '') {
                    $ext = $this->guessExtensionFromPath($sourcePath) ?? 'bin';
                }
                $storedName = uniqid('att_', true) . '.' . preg_replace('/[^a-z0-9]/', '', strtolower($ext));

                $destPath = $uploadsDir . DIRECTORY_SEPARATOR . $storedName;
                if (!@copy($sourcePath, $destPath)) {
                    $result->errors++;
                    $result->errorMessages[] = "Attachment #{$row['attachment_id']}: kopyalama başarısız.";
                    continue;
                }

                $fileSize = (int) $row['file_size'];
                if ($fileSize <= 0) {
                    $fileSize = (int) @filesize($destPath);
                }
                $mimeType = $this->guessMimeType($ext);

                $createdAt = date('Y-m-d H:i:s', (int) $row['attach_date']);

                $insert->execute([
                    $newPostId,
                    $newUserId,
                    $originalName,
                    $storedName,
                    $mimeType,
                    $fileSize,
                    $createdAt,
                ]);

                $mapper->add('attachment', (int) $row['attachment_id'], (int) $targetPdo->lastInsertId());
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Attachment #{$row['attachment_id']}: {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function getUploadsAttachmentsDir(): ?string
    {
        $base = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 4);
        $localRoot = trim(str_replace('\\', '/', (string) Setting::getValue('storage_local_path', 'uploads')), '/');
        if (!in_array($localRoot, ['uploads', 'Content/storage/uploads'], true)) {
            $localRoot = 'uploads';
        }
        $dir = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $localRoot) . DIRECTORY_SEPARATOR . 'attachments';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return is_dir($dir) && is_writable($dir) ? $dir : null;
    }

    /**
     * XenForo 2: internal_data/attachments/{floor(data_id/1000)}/{data_id}-{file_hash}.data
     * If file_path is set in xf_attachment_data it overrides (legacy/custom). Otherwise use default path.
     */
    private function resolveXenForoFilePath(string $internalDataPath, int $dataId, string $dataFilePath, string $fileHash): ?string
    {
        if ($dataFilePath !== '' && $dataFilePath !== null) {
            $path = $internalDataPath . DIRECTORY_SEPARATOR . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dataFilePath), DIRECTORY_SEPARATOR);
            return is_file($path) ? $path : null;
        }
        $group = (int) floor($dataId / 1000);
        $dir = $internalDataPath . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . $group;
        if ($fileHash !== '') {
            $path = $dir . DIRECTORY_SEPARATOR . $dataId . '-' . $fileHash . '.data';
            if (is_file($path)) {
                return $path;
            }
        }
        if (!is_dir($dir)) {
            return null;
        }
        $files = @scandir($dir);
        if (!$files) {
            return null;
        }
        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || !is_file($dir . DIRECTORY_SEPARATOR . $f)) {
                continue;
            }
            if (
                $f === (string) $dataId . '-' . $fileHash . '.data'
                || strpos($f, (string) $dataId . '-') === 0
                || $f === (string) $dataId
            ) {
                return $dir . DIRECTORY_SEPARATOR . $f;
            }
        }
        return null;
    }

    private function guessExtensionFromPath(string $path): ?string
    {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = @finfo_file($finfo, $path);
        @finfo_close($finfo);
        if ($mime === false) {
            return null;
        }
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        return $map[$mime] ?? null;
    }

    private function guessMimeType(string $ext): string
    {
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip', 'rar' => 'application/x-rar-compressed',
        ];
        return $map[strtolower($ext)] ?? 'application/octet-stream';
    }
}
