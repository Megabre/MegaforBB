<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MailTemplate;
use Forecor\Core\Application;

/**
 * Sistem mail şablonlarını veritabanından okur. Boş ise null döner (çağıran mevcut lang() mantığını kullanır).
 */
class MailTemplateService
{
    public function __construct(
        private readonly Application $app
    ) {
    }

    /**
     * Şablon konu metnini döndürür. Placeholder'lar replace ile değiştirilir.
     * Boş veya yoksa null.
     */
    public function getSubject(string $templateKey, array $replace = []): ?string
    {
        $t = MailTemplate::where('template_key', $templateKey)->first(['subject']);
        if (!$t || trim((string) $t->subject) === '') {
            return null;
        }
        return $this->replacePlaceholders((string) $t->subject, $replace);
    }

    /**
     * Şablon HTML gövdesini döndürür. Placeholder'lar replace ile değiştirilir.
     * Boş veya yoksa null.
     */
    public function getBodyHtml(string $templateKey, array $replace = []): ?string
    {
        $t = MailTemplate::where('template_key', $templateKey)->first(['body_html']);
        if (!$t || trim((string) $t->body_html) === '') {
            return null;
        }
        return $this->replacePlaceholders((string) $t->body_html, $replace);
    }

    private function replacePlaceholders(string $text, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $val = (string) $value;
            $text = str_replace(':' . $key, $val, $text);
            $text = str_replace('{' . $key . '}', $val, $text);
        }
        return $text;
    }

    /**
     * Metin/HTML içindeki :key ve {key} etiketlerini verilen değerlerle değiştirir.
     * Mesaj gönder ve toplu mesajda editör çıktısı için kullanılır.
     */
    public static function replaceInContent(string $content, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $val = (string) $value;
            $content = str_replace(':' . $key, $val, $content);
            $content = str_replace('{' . $key . '}', $val, $content);
        }
        return $content;
    }

    /**
     * Editör / şablonlarda kullanılabilecek etiket listesi (açıklamalı).
     * Mesaj gönder, toplu mesaj ve mail şablonlarında kullanılır.
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            'username' => 'Kullanıcı adı',
            'name' => 'Ad / görünen isim',
            'email' => 'E-posta adresi',
            'site_name' => 'Site adı',
            'website_name' => 'Site adı (website_name)',
            'website' => 'Site URL',
            'verify_url' => 'E-posta doğrulama linki',
            'code' => 'Doğrulama kodu',
            'url' => 'Şifre sıfırlama linki',
            'reply_body' => 'İletişim cevabı metni',
        ];
    }
}
