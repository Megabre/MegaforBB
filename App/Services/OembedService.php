<?php

declare(strict_types=1);

namespace App\Services;

/**
 * OembedService
 *
 * Mesaj ve konu içeriklerindeki dış bağlantıları (YouTube, Twitter, Vimeo vb.)
 * Regex ile tespit ederek otomatik şekilde <iframe> zengin medya oynatıcılarına dönüştürür.
 */
class OembedService
{
    /**
     * @var array<string, array<string, string>> Regex eşleştirme kuralları ve şablonları
     * Sadece RegExp gövdesini (delimiter olmadan) barındırır.
     */
    protected array $providers = [
        'youtube' => [
            'pattern' => '(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?.*v=|embed\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})',
            'template' => '<div class="media-container my-4 rounded-xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700 aspect-video"><iframe src="https://www.youtube.com/embed/$1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen class="w-full h-full"></iframe></div>'
        ],
        'vimeo' => [
            'pattern' => '(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]+\/videos\/|album\/\d+\/video\/|video\/|)(\d+)(?:$|\/|\?)',
            'template' => '<div class="media-container my-4 rounded-xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700 aspect-video"><iframe src="https://player.vimeo.com/video/$1" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen class="w-full h-full"></iframe></div>'
        ],
        'twitter' => [
            'pattern' => '(?:https?:\/\/)?(?:www\.)?(?:twitter\.com|x\.com)\/(?:[^\/]+)\/status\/(\d+)',
            'template' => '<div class="media-container my-4"><blockquote class="twitter-tweet" data-theme="dark"><a href="https://twitter.com/x/status/$1">Tweet yükleniyor...</a></blockquote><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script></div>'
        ],
        'twitch' => [
            'pattern' => '(?:https?:\/\/)?(?:www\.)?twitch\.tv\/([a-zA-Z0-9_]+)',
            'template' => '<div class="media-container my-4 rounded-xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700 aspect-video"><iframe src="https://player.twitch.tv/?channel=$1&parent=CURRENT_HOST" frameborder="0" allowfullscreen="true" scrolling="no" class="w-full h-full"></iframe></div>'
        ],
        'spotify' => [
            'pattern' => '(?:https?:\/\/)?(?:open\.)?spotify\.com\/(track|album|playlist|show|episode)\/([a-zA-Z0-9]+)',
            'template' => '<div class="media-container my-4 rounded-xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700" style="height:152px;"><iframe src="https://open.spotify.com/embed/$1/$2" width="100%" height="152" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe></div>'
        ],
        'soundcloud' => [
            'pattern' => '(?:https?:\/\/)?(?:www\.)?soundcloud\.com\/([\w-]+)\/([\w-]+)',
            'template' => '<div class="media-container my-4 rounded-xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700"><iframe width="100%" height="166" scrolling="no" frameborder="no" allow="autoplay" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/$1/$2&color=%23ff5500&auto_play=false&hide_related=false&show_comments=true&show_user=true&show_reposts=false&show_teaser=true"></iframe></div>'
        ],
    ];

    /**
     * Verilen HTML (veya düz metin) içerisindeki HTML a etiketlerine çevrilmiş linkleri <iframe>'lere dönüştürür.
     * core_body_to_html fonksiyonundan sonra çalıştırılır.
     *
     * @param string $html
     * @return string
     */
    public function parseLinks(string $html): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        foreach ($this->providers as $provider => $data) {
            $template = $data['template'];

            if ($provider === 'twitch') {
                $template = str_replace('CURRENT_HOST', $host, $template);
            }

            // core_body_to_html (kaba linkleri <a> etiketine çeviriyor) sonrası çalıştığı için,
            // href="https://youtube.com/..." formatındaki <a> etiketlerini hedefliyoruz.
            // Fakat <a href="link">Tıkla</a> değil de, <a href="link">link</a> şeklinde olan (autolink)
            // yapılarını Oembed'e çevirmek daha sağlıklıdır.
            // Biz basitçe her URL formatında sarmalanmış autolink olan <a> etiketlerini hedefleyeceğiz.

            // $0 -> Tüm a etiketi
            // Şablonlarda $1, $2 gibi yakalamalar olduğu için replace callback daha esnek.
            $pattern = '~<a\s[^>]*href=["\'](' . $data['pattern'] . ')["\'][^>]*>.*?</a>~xi';

            $html = preg_replace($pattern, $template, $html);
        }

        return $html;
    }
}
