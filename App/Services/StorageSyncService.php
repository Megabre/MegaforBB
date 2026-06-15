<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Yerel yükleme dizini değiştiğinde veya admin "Senkronize et" dediğinde:
 * 1) Veritabanındaki tüm eski path referansları "uploads/..." formatına güncellenir.
 * 2) Eski kök dizindeki dosyalar mevcut köke taşınır; eski dizinden silinir.
 */
class StorageSyncService
{
    private string $basePath;

    private const ROOTS = ['uploads', 'Content/storage/uploads'];

    /** Eski BaseController hatası: dosyalar bu dizine yazılıyordu. */
    private const LEGACY_ROOT = 'Content/upload/uploads';

    /** Veritabanında eski path önekleri → hep "uploads/" ile değiştirilecek. */
    private const LEGACY_PATH_PREFIXES = [
        'Content/upload/uploads/',
        'Content/upload/uploads\\',
        'Content\\upload\\uploads\\',
        'Content\\upload\\uploads/',
        'Content/storage/uploads/',
        'Content/storage/uploads\\',
        'Content\\storage\\uploads\\',
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    }

    /**
     * Mevcut ayardaki yerel kök dizini.
     */
    public function getCurrentRoot(): string
    {
        $root = trim(str_replace('\\', '/', (string) Setting::getValue('storage_local_path', 'uploads')), '/');
        if ($root === '' || !in_array($root, self::ROOTS, true)) {
            return 'uploads';
        }
        return $root;
    }

    /**
     * Diğer kök dizin (taşıma kaynağı).
     */
    public function getOtherRoot(): string
    {
        $current = $this->getCurrentRoot();
        return $current === 'uploads' ? 'Content/storage/uploads' : 'uploads';
    }

    /**
     * Veritabanındaki tüm eski yükleme path'lerini "uploads/..." formatına günceller.
     * users.avatar_path, users.cover_photo_path, posts.body, posts.body_html.
     *
     * @return array{updated_users: int, updated_posts: int}
     */
    public function normalizeDatabasePaths(): array
    {
        $updatedUsers = 0;
        $updatedPosts = 0;

        $users = DB::table('users')->get(['id', 'avatar_path', 'cover_photo_path']);
        foreach ($users as $u) {
            $avatar = $this->normalizePathString((string) $u->avatar_path);
            $cover = $this->normalizePathString((string) $u->cover_photo_path);
            if ($avatar !== (string) $u->avatar_path || $cover !== (string) $u->cover_photo_path) {
                DB::table('users')->where('id', $u->id)->update([
                    'avatar_path' => $avatar !== '' ? $avatar : null,
                    'cover_photo_path' => $cover !== '' ? $cover : null,
                ]);
                $updatedUsers++;
            }
        }

        $posts = DB::table('posts')->get(['id', 'body', 'body_html']);
        foreach ($posts as $p) {
            $body = $this->normalizePathString((string) $p->body);
            $bodyHtml = $this->normalizePathString((string) $p->body_html);
            if ($body !== (string) $p->body || $bodyHtml !== (string) $p->body_html) {
                DB::table('posts')->where('id', $p->id)->update(['body' => $body, 'body_html' => $bodyHtml]);
                $updatedPosts++;
            }
        }

        return ['updated_users' => $updatedUsers, 'updated_posts' => $updatedPosts];
    }

    private function normalizePathString(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $out = $value;
        foreach (self::LEGACY_PATH_PREFIXES as $prefix) {
            $out = str_ireplace($prefix, 'uploads/', $out);
        }
        $out = preg_replace('#Content/upload/uploads[/\\\\]#i', 'uploads/', $out);
        $out = preg_replace('#Content/storage/uploads[/\\\\]#i', 'uploads/', $out);
        return $out;
    }

    /**
     * Eski kök dizindeki tüm dosyaları mevcut köke taşır; kaynak dosyayı siler.
     * Önce veritabanı path'leri normalize edilir, sonra legacy ve diğer kökten taşıma yapılır.
     *
     * @return array{moved: int, errors: list<string>, updated_users: int, updated_posts: int}
     */
    public function syncToCurrentRoot(): array
    {
        $norm = $this->normalizeDatabasePaths();
        $totalMoved = 0;
        $errors = [];
        $current = $this->getCurrentRoot();

        $legacyDir = $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, self::LEGACY_ROOT);
        if (is_dir($legacyDir)) {
            $r = $this->moveDirectoryContentsToCurrent($legacyDir, '');
            $totalMoved += $r['moved'];
            $errors = array_merge($errors, $r['errors']);
            $this->removeEmptyDirs($legacyDir);
        }

        $other = $this->getOtherRoot();
        $sourceDir = $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $other);
        if (is_dir($sourceDir)) {
            $r = $this->moveDirectoryContentsToCurrent($sourceDir, $current);
            $totalMoved += $r['moved'];
            $errors = array_merge($errors, $r['errors']);
            $this->removeEmptyDirs($sourceDir);
        }

        $r = $this->copyReferencedFilesToCurrent($current);
        $totalMoved += $r['moved'];
        $errors = array_merge($errors, $r['errors']);

        return [
            'moved' => $totalMoved,
            'errors' => $errors,
            'updated_users' => $norm['updated_users'],
            'updated_posts' => $norm['updated_posts'],
        ];
    }

    /**
     * @return array{moved: int, errors: list<string>}
     */
    private function moveDirectoryContentsToCurrent(string $sourceDir, string $currentRootPrefix): array
    {
        $current = $currentRootPrefix !== '' ? $currentRootPrefix : $this->getCurrentRoot();
        $moved = 0;
        $errors = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $sourceDirNorm = rtrim(str_replace('\\', '/', $sourceDir), '/');
        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $fullSource = $item->getPathname();
            $relative = substr(str_replace('\\', '/', $fullSource), strlen($sourceDirNorm));
            $relative = ltrim($relative, '/');
            if ($relative === '' || strpos($relative, '..') !== false) {
                continue;
            }
            $destFull = $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $current . '/' . $relative);
            $destDir = dirname($destFull);
            if (!is_dir($destDir)) {
                if (!@mkdir($destDir, 0755, true)) {
                    $errors[] = 'Dizin oluşturulamadı: ' . $destDir;
                    continue;
                }
            }
            if (@copy($fullSource, $destFull)) {
                @unlink($fullSource);
                $moved++;
            } else {
                $errors[] = 'Kopyalanamadı: ' . $relative;
            }
        }
        return ['moved' => $moved, 'errors' => $errors];
    }

    /**
     * Veritabanında referansı olan (users avatar/cover, attachments) dosyaları
     * mevcut kökte yoksa legacy veya diğer kökten kopyalar.
     *
     * @return array{moved: int, errors: list<string>}
     */
    private function copyReferencedFilesToCurrent(string $currentRoot): array
    {
        $paths = [];
        $rows = DB::table('users')->whereNotNull('avatar_path')->where('avatar_path', '!=', '')->pluck('avatar_path');
        foreach ($rows as $p) {
            $paths[] = $this->pathToSuffix((string) $p);
        }
        $rows = DB::table('users')->whereNotNull('cover_photo_path')->where('cover_photo_path', '!=', '')->pluck('cover_photo_path');
        foreach ($rows as $p) {
            $paths[] = $this->pathToSuffix((string) $p);
        }
        $attachments = DB::table('attachments')->pluck('stored_name');
        foreach ($attachments as $name) {
            $paths[] = 'attachments/' . (string) $name;
        }
        $paths = array_unique(array_filter($paths));
        $moved = 0;
        $errors = [];
        $currentDir = $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $currentRoot);
        $sources = [
            $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, self::LEGACY_ROOT),
            $this->basePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $this->getOtherRoot()),
        ];
        foreach ($paths as $suffix) {
            if ($suffix === '' || strpos($suffix, '..') !== false) {
                continue;
            }
            $destFull = $currentDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix);
            if (is_file($destFull)) {
                continue;
            }
            $destDir = dirname($destFull);
            foreach ($sources as $sourceDir) {
                if (!is_dir($sourceDir)) {
                    continue;
                }
                $srcFull = $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix);
                if (is_file($srcFull)) {
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, 0755, true);
                    }
                    if (@copy($srcFull, $destFull)) {
                        @unlink($srcFull);
                        $moved++;
                    } else {
                        $errors[] = 'Kopyalanamadı: ' . $suffix;
                    }
                    break;
                }
            }
        }
        return ['moved' => $moved, 'errors' => $errors];
    }

    private function pathToSuffix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '') {
            return '';
        }
        if (stripos($path, 'uploads/') === 0) {
            return substr($path, 8);
        }
        $idx = stripos($path, 'uploads/');
        if ($idx !== false) {
            return substr($path, $idx + 8);
        }
        return '';
    }

    private function removeEmptyDirs(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }
        $list = array_diff($items, ['.', '..']);
        foreach ($list as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeEmptyDirs($path);
            }
        }
        $list = @scandir($dir);
        if (is_array($list) && count(array_diff($list, ['.', '..'])) === 0) {
            @rmdir($dir);
        }
    }
}
