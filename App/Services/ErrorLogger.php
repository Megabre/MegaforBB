<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Merkezi hata ve istisna loglama.
 * APP_DEBUG kapalı olsa bile tüm hatalar dosyaya yazılır.
 * Log dosyası: Content/storage/logs/megaforbb-YYYY-MM-DD.log
 */
class ErrorLogger
{
    private const LOG_DIR = 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    private const LOG_PREFIX = 'megaforbb-';
    private const MAX_RECENT_LINES = 200;

    private static ?string $basePath = null;
    private static bool $enabled = true;

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    public static function getLogPath(): string
    {
        $dir = self::getLogDir();
        return $dir . DIRECTORY_SEPARATOR . self::LOG_PREFIX . date('Y-m-d') . '.log';
    }

    public static function getLogDir(): string
    {
        $base = self::$basePath ?? (defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : '');
        if ($base === '') {
            $base = dirname(__DIR__, 2); // App/Services -> proje kökü
        }
        $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base), DIRECTORY_SEPARATOR);
        return $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::LOG_DIR);
    }

    /**
     * İstisnayı dosyaya yazar (mesaj, dosya:satır, trace, istek bilgisi).
     */
    public static function logException(\Throwable $e, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }
        $line = self::formatException($e, $context);
        self::write($line, 'ERROR');
    }

    /**
     * PHP error (E_WARNING, E_NOTICE vb.) satırı yazar.
     */
    public static function logError(int $severity, string $message, string $file = '', int $line = 0, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }
        $level = self::severityToLevel($severity);
        $loc = ($file !== '' ? $file . ':' . $line : '');
        $req = self::requestSummary();
        $ctx = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] ' . $message . ($loc !== '' ? ' @ ' . $loc : '') . ' ' . $req . $ctx . "\n";
        self::write($line, $level);
    }

    /**
     * Genel mesaj loglar (debug, info, warning).
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }
        $req = self::requestSummary();
        $ctx = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . ' ' . $req . $ctx . "\n";
        self::write($line, strtoupper($level));
    }

    /**
     * Son N satırı okur (admin panelde göstermek için).
     *
     * @return array<int, string>
     */
    public static function getRecentLines(int $maxLines = self::MAX_RECENT_LINES): array
    {
        $path = self::getLogPath();
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $lines = array_filter(explode("\n", $content));
        $lines = array_slice(array_values($lines), -$maxLines);
        return $lines;
    }

    /**
     * Log dosyasını parse edip giriş listesi döner (admin panelde okunaklı göstermek için).
     * Her giriş: ['date' => '...', 'level' => 'ERROR'|'WARNING'|..., 'summary' => '...', 'body' => '...'].
     *
     * @return array<int, array{date: string, level: string, summary: string, body: string}>
     */
    public static function getRecentEntries(int $maxEntries = 100): array
    {
        $path = self::getLogPath();
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $lines = explode("\n", $content);
        $entries = [];
        $current = null;
        $datePattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s*\[([^\]]+)\]\s*(.*)$/';

        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match($datePattern, $line, $m)) {
                if ($current !== null) {
                    $entries[] = $current;
                }
                $current = [
                    'date'    => $m[1],
                    'level'   => strtoupper(trim($m[2])),
                    'summary' => trim($m[3]),
                    'body'    => '',
                ];
            } elseif ($current !== null && $line !== '') {
                $current['body'] .= $line . "\n";
            }
        }
        if ($current !== null) {
            $entries[] = $current;
        }
        return array_values(array_slice($entries, -$maxEntries));
    }

    private static function formatException(\Throwable $e, array $context): string
    {
        $req = self::requestSummary();
        $ctx = $context !== [] ? "\nContext: " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
        $prev = $e->getPrevious();
        $prevStr = $prev ? "\nPrevious: " . $prev->getMessage() . ' in ' . $prev->getFile() . ':' . $prev->getLine() : '';
        return sprintf(
            "[%s] [EXCEPTION] %s in %s:%d\nRequest: %s\nTrace:\n%s%s%s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $req,
            $e->getTraceAsString(),
            $prevStr,
            $ctx
        );
    }

    private static function requestSummary(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '-';
        $method = $_SERVER['REQUEST_METHOD'] ?? '-';
        return "URI={$method} {$uri}";
    }

    private static function severityToLevel(int $severity): string
    {
        $map = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'ERROR',
            E_CORE_WARNING => 'WARNING',
            E_COMPILE_ERROR => 'ERROR',
            E_COMPILE_WARNING => 'WARNING',
            E_USER_ERROR => 'ERROR',
            E_USER_WARNING => 'WARNING',
            E_USER_NOTICE => 'NOTICE',
            E_STRICT => 'DEBUG',
            E_RECOVERABLE_ERROR => 'ERROR',
            E_DEPRECATED => 'DEBUG',
            E_USER_DEPRECATED => 'DEBUG',
        ];
        return $map[$severity] ?? 'ERROR';
    }

    private static function write(string $line, string $level): void
    {
        $dir = self::getLogDir();
        if ($dir === '') {
            return;
        }
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log('[ErrorLogger] Log dizini oluşturulamadı: ' . $dir);
                return;
            }
        }
        $path = $dir . DIRECTORY_SEPARATOR . self::LOG_PREFIX . date('Y-m-d') . '.log';
        $written = @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        if ($written === false) {
            error_log('[ErrorLogger] Log dosyasına yazılamadı: ' . $path . ' | Mesaj: ' . substr($line, 0, 200));
        }
    }
}
