<?php

declare(strict_types=1);

namespace Forecor\Core;

class Str
{
    /**
     * URL ve dosya adı için güvenli slug (platform bağımsız, locale'den etkilenmez).
     * Türkçe karakterleri ASCII karşılıklarına çevirir; boşluk/özel karakterleri ayırıcı yapar.
     */
    public static function slug(string $text, string $separator = '-'): string
    {
        $tr = [
            'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g',
            'ı' => 'i', 'İ' => 'i', 'I' => 'i', 'ö' => 'o', 'Ö' => 'o',
            'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
            'â' => 'a', 'Â' => 'a', 'î' => 'i', 'Î' => 'i', 'û' => 'u', 'Û' => 'u',
        ];
        $text = strtr($text, $tr);

        if (function_exists('transliterator_transliterate')) {
            $t = @transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
            if ($t !== false && $t !== null) {
                $text = $t;
            }
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s\-]/', '', $text) ?? $text;
        $text = preg_replace('/[\s\-]+/', $separator, $text) ?? $text;

        return trim($text, $separator);
    }
}
