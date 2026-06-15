<?php

declare(strict_types=1);

namespace App\Services;

/**
 * HTML çıktısını küçültür (Performans → Minify ayarları ile kullanılır).
 * - HTML: etiket arası ve etiket içi boşluk, yorumlar
 * - Inline CSS (minify_css): <style> içeriği
 * - Inline JS (minify_js): <script> içeriği (application/ld+json hariç)
 */
class HtmlMinifier
{
    private const PLACEHOLDER_STYLE = "\x00%%MFBB_STYLE_%d%%\x00";
    private const PLACEHOLDER_SCRIPT = "\x00%%MFBB_SCRIPT_%d%%\x00";

    /**
     * @param array{minify_html?: bool, minify_css?: bool, minify_js?: bool} $options
     */
    public static function minify(string $html, array $options = []): string
    {
        if ($html === '') {
            return $html;
        }
        $minifyHtml = $options['minify_html'] ?? true;
        $minifyCss = $options['minify_css'] ?? false;
        $minifyJs = $options['minify_js'] ?? false;

        $styles = [];
        $scripts = [];

        // 1. <style>...</style> bloklarını çıkar (içerik bozulmasın diye)
        $html = preg_replace_callback('/<style(\s[^>]*)?>([\s\S]*?)<\/style>/i', static function ($m) use (&$styles, $minifyCss) {
            $id = count($styles);
            $content = $m[2];
            if ($minifyCss) {
                $content = self::minifyCss($content);
            }
            $styles[$id] = '<style' . $m[1] . '>' . $content . '</style>';
            return sprintf(self::PLACEHOLDER_STYLE, $id);
        }, $html);

        // 2. <script>...</script> bloklarını çıkar (içerik bozulmasın diye; type=application/ld+json vb. korunur)
        $html = preg_replace_callback('/<script(\s[^>]*)?>([\s\S]*?)<\/script>/i', static function ($m) use (&$scripts, $minifyJs) {
            $attrs = $m[1] ?? '';
            $content = $m[2];
            $isJson = preg_match('/\btype\s*=\s*["\']?\s*application\/(?:ld\+)?json/i', $attrs);
            if ($minifyJs && !$isJson) {
                $content = self::minifyJs($content);
            }
            $id = count($scripts);
            $scripts[$id] = '<script' . $attrs . '>' . $content . '</script>';
            return sprintf(self::PLACEHOLDER_SCRIPT, $id);
        }, $html);

        if ($minifyHtml) {
            // 3. Etiketler arası boşluk/newline kaldır
            $html = preg_replace('/>\s+</s', '><', $html);

            // 4. Collapse whitespace/newlines inside tags (name + attributes) to single space
            $html = preg_replace_callback('/<([^>]+)>/', static function ($m) {
                return '<' . preg_replace('/\s+/', ' ', trim($m[1])) . '>';
            }, $html);

            // 5. Koşullu olmayan HTML yorumlarını kaldır
            $html = preg_replace('/<!--(?!\s*\[).*?-->/s', '', $html);
        }

        $html = trim($html);

        // 6. Placeholder'ları geri koy
        foreach ($styles as $id => $replacement) {
            $html = str_replace(sprintf(self::PLACEHOLDER_STYLE, $id), $replacement, $html);
        }
        foreach ($scripts as $id => $replacement) {
            $html = str_replace(sprintf(self::PLACEHOLDER_SCRIPT, $id), $replacement, $html);
        }

        return $html;
    }

    private static function minifyCss(string $css): string
    {
        $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        return trim($css);
    }

    private static function minifyJs(string $js): string
    {
        // Satır ve blok yorumları kaldır (string içinde olsa da basit yaklaşım; regex string'i atlayamaz)
        // Güvenli yol: sadece gereksiz boşluk/newline'ı tek boşluğa indir
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }
}
