<?php

declare(strict_types=1);

namespace App\Services\Import;

class BBCodeConverter
{
    private static array $xfSizeMap = [
        '1' => '10px',
        '2' => '13px',
        '3' => '16px',
        '4' => '18px',
        '5' => '24px',
        '6' => '32px',
        '7' => '48px',
    ];

    private static function extractYouTubeId(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('/^[a-zA-Z0-9_-]{6,}$/', $input)) {
            return $input;
        }
        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/))([a-zA-Z0-9_-]{6,})~i', $input, $m)) {
            return $m[1];
        }
        if (preg_match('/\bv=([a-zA-Z0-9_-]{6,})/i', $input, $m)) {
            return $m[1];
        }
        return null;
    }

    private static function extractDailymotionId(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('/^[a-zA-Z0-9]+$/', $input)) {
            return $input;
        }
        if (preg_match('~(?:dailymotion\.com/video/|dai\.ly/)([a-zA-Z0-9]+)~i', $input, $m)) {
            return $m[1];
        }
        return null;
    }

    public static function convert(string $bbcode): string
    {
        $text = $bbcode;
        // Kopyala-yapıştır ile gelen aşırı boş satırları azalt (3+ → 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        $codeBlocks = [];
        $placeholder = "\x00CODE_BLOCK_%d\x00";

        $text = preg_replace_callback(
            '/\[CODE(?:=([^\]]*))?\](.*?)\[\/CODE\]/si',
            function ($m) use (&$codeBlocks, $placeholder) {
                $index = count($codeBlocks);
                $lang = trim($m[1] ?? '');
                $code = $m[2];
                $langClass = $lang !== '' ? ' language-' . htmlspecialchars($lang) : '';
                $codeBlocks[$index] = '<pre class="mfbb-code-block"><code class="mfbb-code' . $langClass . '">' . htmlspecialchars($code) . '</code></pre>';
                return sprintf($placeholder, $index);
            },
            $text
        );

        $text = preg_replace_callback(
            '/\[ICODE\](.*?)\[\/ICODE\]/si',
            function ($m) {
                return '<code>' . htmlspecialchars($m[1]) . '</code>';
            },
            $text
        );

        $text = preg_replace('/\[B\](.*?)\[\/B\]/si', '<strong>$1</strong>', $text);
        $text = preg_replace('/\[I\](.*?)\[\/I\]/si', '<em>$1</em>', $text);
        $text = preg_replace('/\[U\](.*?)\[\/U\]/si', '<u>$1</u>', $text);
        $text = preg_replace('/\[S\](.*?)\[\/S\]/si', '<del>$1</del>', $text);

        $text = preg_replace(
            '/\[URL=([^\]]+)\](.*?)\[\/URL\]/si',
            '<a href="$1" rel="nofollow noopener" target="_blank">$2</a>',
            $text
        );
        $text = preg_replace(
            '/\[URL\](.*?)\[\/URL\]/si',
            '<a href="$1" rel="nofollow noopener" target="_blank">$1</a>',
            $text
        );

        // XenForo: [IMG alt="..."]url[/IMG] veya [IMG alt='...']url[/IMG] (URL aynı veya sonraki satırda olabilir)
        $text = preg_replace_callback(
            '/\[IMG\s+alt=(["\'])([^"\']*)\1\s*\]\s*(.*?)\s*\[\/IMG\]/si',
            function ($m) {
                $alt = str_replace(['<', '>', '"'], ['&lt;', '&gt;', '&quot;'], trim($m[2]));
                $url = trim(preg_replace('/\s+/', ' ', $m[3]));
                if ($url === '') {
                    return $m[0];
                }
                return '<img src="' . htmlspecialchars($url) . '" alt="' . $alt . '" loading="lazy" class="img-fluid">';
            },
            $text
        );
        $text = preg_replace(
            '/\[IMG\](.*?)\[\/IMG\]/si',
            '<img src="$1" loading="lazy" class="img-fluid">',
            $text
        );

        $text = preg_replace_callback(
            '/\[QUOTE="([^"]*?)(?:,\s*post:\s*\d+)?(?:,\s*member:\s*\d+)?"\](.*?)\[\/QUOTE\]/si',
            function ($m) {
                $author = $m[1];
                return '<blockquote class="bb-quote" data-author="' . htmlspecialchars($author) . '">'
                    . '<div class="quote-header"><strong>' . htmlspecialchars($author) . '</strong> dedi:</div>'
                    . $m[2]
                    . '</blockquote>';
            },
            $text
        );
        $text = preg_replace(
            '/\[QUOTE\](.*?)\[\/QUOTE\]/si',
            '<blockquote class="bb-quote">$1</blockquote>',
            $text
        );

        $text = preg_replace_callback(
            '/\[LIST(=1)?\](.*?)\[\/LIST\]/si',
            function ($m) {
                $ordered = ($m[1] === '=1');
                $tag = $ordered ? 'ol' : 'ul';
                $items = preg_split('/\[\*\]/i', $m[2]);
                $html = "<{$tag}>";
                foreach ($items as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $html .= '<li>' . $item . '</li>';
                    }
                }
                $html .= "</{$tag}>";
                return $html;
            },
            $text
        );

        $text = preg_replace_callback(
            '/\[HEADING=([1-6])\](.*?)\[\/HEADING\]/si',
            function ($m) {
                $level = $m[1];
                return "<h{$level}>{$m[2]}</h{$level}>";
            },
            $text
        );

        $text = preg_replace_callback(
            '/\[MEDIA=youtube\](.*?)\[\/MEDIA\]/si',
            function ($m) {
                $videoId = self::extractYouTubeId($m[1] ?? '');
                if ($videoId === null) {
                    return $m[0];
                }
                $videoId = htmlspecialchars($videoId);
                return '<div class="mfbb-media-embed ratio ratio-16x9">'
                    . '<iframe src="https://www.youtube.com/embed/' . $videoId . '" allowfullscreen></iframe>'
                    . '</div>';
            },
            $text
        );
        $text = preg_replace_callback(
            '/\[MEDIA=dailymotion\](.*?)\[\/MEDIA\]/si',
            function ($m) {
                $videoId = self::extractDailymotionId($m[1] ?? '');
                if ($videoId === null) {
                    return $m[0];
                }
                $videoId = htmlspecialchars($videoId);
                return '<div class="mfbb-media-embed ratio ratio-16x9">'
                    . '<iframe src="https://www.dailymotion.com/embed/video/' . $videoId . '" allowfullscreen></iframe>'
                    . '</div>';
            },
            $text
        );

        $text = preg_replace_callback(
            '/\[COLOR=([^\]]+)\](.*?)\[\/COLOR\]/si',
            function ($m) {
                return '<span style="color:' . htmlspecialchars($m[1]) . '">' . $m[2] . '</span>';
            },
            $text
        );

        $text = preg_replace_callback(
            '/\[SIZE=([^\]]+)\](.*?)\[\/SIZE\]/si',
            function ($m) {
                $size = $m[1];
                if (isset(self::$xfSizeMap[$size])) {
                    $px = self::$xfSizeMap[$size];
                } elseif (preg_match('/^\d+$/', $size)) {
                    $px = $size . 'px';
                } else {
                    $px = $size;
                }
                return '<span style="font-size:' . htmlspecialchars($px) . '">' . $m[2] . '</span>';
            },
            $text
        );

        $text = preg_replace_callback(
            '/\[SPOILER="([^"]+)"\](.*?)\[\/SPOILER\]/si',
            function ($m) {
                return '<details class="bb-spoiler"><summary>' . htmlspecialchars($m[1]) . '</summary><div>' . $m[2] . '</div></details>';
            },
            $text
        );
        $text = preg_replace(
            '/\[SPOILER\](.*?)\[\/SPOILER\]/si',
            '<details class="bb-spoiler"><summary>Spoiler</summary><div>$1</div></details>',
            $text
        );

        $text = preg_replace(
            '/\[ATTACH\](.*?)\[\/ATTACH\]/si',
            '<!-- attachment:$1 -->',
            $text
        );

        $text = preg_replace_callback(
            '/\[USER=(\d+)\](.*?)\[\/USER\]/si',
            function ($m) {
                $username = trim((string) ($m[2] ?? ''));
                if ($username === '') {
                    return '@' . htmlspecialchars((string) $m[1]);
                }

                return '<a href="' . core_e(core_url('member/' . rawurlencode($username))) . '" class="bb-user-mention">@' . htmlspecialchars($username) . '</a>';
            },
            $text
        );

        $text = preg_replace('/\[LEFT\](.*?)\[\/LEFT\]/si', '<div style="text-align:left">$1</div>', $text);
        $text = preg_replace('/\[CENTER\](.*?)\[\/CENTER\]/si', '<div style="text-align:center">$1</div>', $text);
        $text = preg_replace('/\[RIGHT\](.*?)\[\/RIGHT\]/si', '<div style="text-align:right">$1</div>', $text);

        $text = preg_replace('/\[HR\](?:\[\/HR\])?/si', '<hr>', $text);

        $text = preg_replace('/\[TABLE\](.*?)\[\/TABLE\]/si', '<table class="table">$1</table>', $text);
        $text = preg_replace('/\[TR\](.*?)\[\/TR\]/si', '<tr>$1</tr>', $text);
        $text = preg_replace('/\[TD\](.*?)\[\/TD\]/si', '<td>$1</td>', $text);
        $text = preg_replace('/\[TH\](.*?)\[\/TH\]/si', '<th>$1</th>', $text);

        $text = str_replace("\n", '<br>', $text);

        foreach ($codeBlocks as $index => $block) {
            $text = str_replace(sprintf($placeholder, $index), $block, $text);
        }

        return $text;
    }

    /**
     * MyBB MyCode → HTML (same as BBCode; MyBB uses [b], [i], [url], [img], [size], [color], [quote], [code], [list] etc.)
     */
    public static function convertMyCode(string $mycode): string
    {
        $text = $mycode;
        $text = preg_replace('/\[email=([^\]]+)\](.*?)\[\/email\]/si', '<a href="mailto:$1">$2</a>', $text);
        $text = preg_replace('/\[email\]([^\[]+)\[\/email\]/si', '<a href="mailto:$1">$1</a>', $text);
        $text = preg_replace('/\[align=(left|center|right)\](.*?)\[\/align\]/si', '<div style="text-align:$1">$2</div>', $text);
        return self::convert($text);
    }
}
