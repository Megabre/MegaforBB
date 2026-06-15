<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Metin içindeki :) :D gibi ifadeleri Unicode emoji veya yönetilen GIF'lere çevirir.
 * Sıfır dış kaynak, strtr ile milisaniyelik render.
 */
class SmileyHelper
{
    /** Varsayılan Unicode emoji eşlemesi (GIF kapalı veya kod eşleşmesi yoksa kullanılır). */
    private const DEFAULT_UNICODE = [
        ':-)' => '🙂',
        ':-(' => '🙁',
        ':)'  => '🙂',
        ':('  => '🙁',
        ':D'  => '😄',
        ':-D' => '😄',
        ';)'  => '😉',
        ';-)' => '😉',
        ':P'  => '😛',
        ':p'  => '😛',
        ':-P' => '😛',
        ':-p' => '😛',
        '8)'  => '😎',
        '8-)' => '😎',
        ':O'  => '😲',
        ':o'  => '😲',
        ':-O' => '😲',
        ':-o' => '😲',
        ':*'  => '😘',
        ':-*' => '😘',
        '<3'  => '❤️',
        ':S'  => '😖',
        ':s'  => '😖',
        ':\\' => '😕',
        ':/'  => '😕',
        ':|'  => '😐',
        ':-|' => '😐',
    ];

    /** URL'lerdeki :/ smiley ile değişmesin diye geçici placeholder. */
    private const URL_PLACEHOLDER_HTTPS = "\x00HTTPS\x00";
    private const URL_PLACEHOLDER_HTTP = "\x00HTTP\x00";

    /**
     * Metni smiley kodlarından emoji/GIF'e dönüştürür.
     * Birleşik harita: önce Unicode (:) vb.), üzerine GIF'li kodlar (logift vb.) eklenir;
     * böylece hem emoji hem GIF aynı mesajda çalışır.
     * https:// ve http:// içindeki :/ smiley ile değiştirilmez.
     */
    public static function parse(string $text): string
    {
        if ($text === '') {
            return '';
        }
        // URL protokollerini geçici olarak kaldır (:/ smiley'e dönüşmesin)
        $text = str_replace('https://', self::URL_PLACEHOLDER_HTTPS, $text);
        $text = str_replace('http://', self::URL_PLACEHOLDER_HTTP, $text);
        $map = self::getParseMap();
        if ($map !== []) {
            $text = strtr($text, $map);
        }
        $text = str_replace(self::URL_PLACEHOLDER_HTTPS, 'https://', $text);
        $text = str_replace(self::URL_PLACEHOLDER_HTTP, 'http://', $text);
        return $text;
    }

    /** Parse için birleşik harita: unicode + GIF (GIF aynı kod için öncelikli). */
    private static function getParseMap(): array
    {
        $unicode = self::getUnicodeMap();
        if (!self::useGif()) {
            return $unicode;
        }
        $gif = self::getGifMap();
        foreach ($gif as $code => $img) {
            $unicode[$code] = $img;
        }
        return self::sortMapByKeyLength($unicode);
    }

    /** Ayarlardan smiley açık mı? */
    public static function isEnabled(): bool
    {
        try {
            return (string) \App\Models\Setting::getCached('smiley_enabled', '1', 300) === '1';
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** GIF kullanılsın mı? (admin'de açıksa ve en az bir GIF smiley varsa) */
    public static function useGif(): bool
    {
        try {
            if ((string) \App\Models\Setting::getCached('smiley_use_gif', '0', 300) !== '1') {
                return false;
            }
            return \App\Models\Smiley::whereNotNull('image_path')->where('image_path', '!=', '')->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Unicode eşleme: DB'deki özel kodlar + varsayılan. Key = kod, value = emoji. */
    private static function getUnicodeMap(): array
    {
        $map = self::DEFAULT_UNICODE;
        try {
            $custom = \App\Models\Smiley::orderBy('sort_order')->orderBy('id')->get(['code', 'unicode_char']);
            foreach ($custom as $row) {
                $code = trim((string) ($row->code ?? ''));
                $char = trim((string) ($row->unicode_char ?? ''));
                if ($code !== '' && $char !== '') {
                    $map[$code] = $char;
                }
            }
        } catch (\Throwable $e) {
        }
        return self::sortMapByKeyLength($map);
    }

    /** GIF eşleme: kod => <img ...> (sadece image_path dolu olanlar). */
    private static function getGifMap(): array
    {
        try {
            $list = \App\Models\Smiley::whereNotNull('image_path')->where('image_path', '!=', '')->orderBy('sort_order')->orderBy('id')->get(['code', 'image_path']);
            $map = [];
            $base = rtrim((string) (core_config('app.url', '') ?? ''), '/');
            if ($base === '') {
                $base = (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
            }
            foreach ($list as $row) {
                $code = trim((string) ($row->code ?? ''));
                $path = trim((string) ($row->image_path ?? ''));
                if ($code === '' || $path === '') {
                    continue;
                }
                $url = $path;
                if (strpos($path, 'http') !== 0) {
                    $url = $base . (strpos($path, '/') === 0 ? $path : '/' . $path);
                }
                $map[$code] = '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="" class="mfbb-smiley mfbb-smiley-gif" loading="lazy">';
            }
            return self::sortMapByKeyLength($map);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Uzun kodlar önce gelsin (:-) :) den önce). */
    private static function sortMapByKeyLength(array $map): array
    {
        uksort($map, static function ($a, $b) {
            $la = strlen($a);
            $lb = strlen($b);
            if ($la !== $lb) {
                return $lb <=> $la; // uzun önce
            }
            return strcmp($a, $b);
        });
        return $map;
    }

    /** Editor/API için Unicode listesi: [ { code, char }, ... ]. */
    public static function listUnicode(): array
    {
        $map = self::getUnicodeMap();
        $out = [];
        foreach ($map as $code => $char) {
            $out[] = ['code' => $code, 'char' => $char];
        }
        return $out;
    }

    /** Editor/API için GIF listesi: [ { code, url }, ... ]. */
    public static function listGifs(): array
    {
        try {
            $list = \App\Models\Smiley::whereNotNull('image_path')->where('image_path', '!=', '')->orderBy('sort_order')->orderBy('id')->get(['code', 'image_path']);
            $base = rtrim((string) (core_config('app.url', '') ?? ''), '/');
            if ($base === '') {
                $base = (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
            }
            $out = [];
            foreach ($list as $row) {
                $path = trim((string) ($row->image_path ?? ''));
                $url = strpos($path, 'http') === 0 ? $path : ($base . (strpos($path, '/') === 0 ? $path : '/' . $path));
                $out[] = ['code' => trim((string) ($row->code ?? '')), 'url' => $url];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
