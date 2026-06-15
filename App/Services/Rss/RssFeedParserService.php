<?php

declare(strict_types=1);

namespace App\Services\Rss;

/**
 * RSS 2.0 ve Atom (yaygın varyantlar) — XenForo Feed\Reader yapısına benzer düz dizi çıktısı.
 *
 * @return array{title:string,entries:array<int,array{id:string,title:string,link:string,content:string,author:string}>}|null
 */
class RssFeedParserService
{
    public function parse(string $xmlString): ?array
    {
        $xmlString = trim($xmlString);
        if ($xmlString === '') {
            return null;
        }

        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            return null;
        }

        $name = strtolower($xml->getName());
        if ($name === 'rss') {
            return $this->parseRss2($xml);
        }
        if ($name === 'feed') {
            return $this->parseAtom($xml);
        }

        return null;
    }

    /**
     * @return array{title:string,entries:array<int,array<string,mixed>>}
     */
    private function parseRss2(\SimpleXMLElement $xml): array
    {
        $title = isset($xml->channel->title) ? $this->cleanText((string) $xml->channel->title) : '';

        $entries = [];
        if (!isset($xml->channel->item)) {
            return ['title' => $title, 'entries' => []];
        }

        $contentNs = 'http://purl.org/rss/1.0/modules/content/';
        foreach ($xml->channel->item as $item) {
            $itemTitle = isset($item->title) ? $this->cleanText((string) $item->title) : '';
            $link = isset($item->link) ? trim((string) $item->link) : '';
            $guid = isset($item->guid) ? trim((string) $item->guid) : '';
            $id = $guid !== '' ? $guid : ($link !== '' ? $link : md5($itemTitle . '|' . $link));
            $desc = isset($item->description) ? (string) $item->description : '';
            $encoded = '';
            $c = $item->children($contentNs);
            if ($c && isset($c->encoded)) {
                $encoded = (string) $c->encoded;
            }
            $body = $encoded !== '' ? $encoded : $desc;
            $author = '';
            if (isset($item->author)) {
                $author = $this->cleanText((string) $item->author);
            } elseif (isset($item->children('http://purl.org/dc/elements/1.1/')->creator)) {
                $author = $this->cleanText((string) $item->children('http://purl.org/dc/elements/1.1/')->creator);
            }

            $entries[] = [
                'id' => $this->truncateId($id),
                'title' => $itemTitle !== '' ? $itemTitle : $this->snippetPlain($body, 120),
                'link' => $link,
                'content' => $body,
                'author' => $author,
            ];
        }

        return ['title' => $title, 'entries' => $entries];
    }

    /**
     * @return array{title:string,entries:array<int,array<string,mixed>>}
     */
    private function parseAtom(\SimpleXMLElement $xml): array
    {
        $atomNs = 'http://www.w3.org/2005/Atom';
        $atom = $xml->children($atomNs);
        $root = isset($atom->entry) ? $atom : $xml;

        $title = isset($root->title) ? $this->cleanText((string) $root->title) : '';

        $entries = [];
        foreach ($root->entry as $entry) {
            $eTitle = isset($entry->title) ? $this->cleanText((string) $entry->title) : '';
            $id = isset($entry->id) ? trim((string) $entry->id) : '';
            $link = '';
            foreach ($entry->link as $lnk) {
                $a = $lnk->attributes();
                if ($a === null) {
                    continue;
                }
                $rel = isset($a['rel']) ? (string) $a['rel'] : '';
                if ($rel === '' || $rel === 'alternate') {
                    $link = isset($a['href']) ? trim((string) $a['href']) : '';
                    if ($link !== '') {
                        break;
                    }
                }
            }
            if ($id === '') {
                $id = $link !== '' ? $link : md5($eTitle . '|' . spl_object_hash($entry));
            }
            $content = '';
            if (isset($entry->content)) {
                $content = (string) $entry->content;
            } elseif (isset($entry->summary)) {
                $content = (string) $entry->summary;
            }
            $author = '';
            if (isset($entry->author->name)) {
                $author = $this->cleanText((string) $entry->author->name);
            }

            $entries[] = [
                'id' => $this->truncateId($id),
                'title' => $eTitle !== '' ? $eTitle : $this->snippetPlain($content, 120),
                'link' => $link,
                'content' => $content,
                'author' => $author,
            ];
        }

        return ['title' => $title, 'entries' => $entries];
    }

    private function cleanText(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\s+/u", ' ', $s) ?? '');
    }

    private function snippetPlain(string $html, int $maxLen): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');

        return $plain !== '' && mb_strlen($plain) > $maxLen ? mb_substr($plain, 0, $maxLen) . '…' : $plain;
    }

    private function truncateId(string $id): string
    {
        if (mb_strlen($id) <= 250) {
            return $id;
        }

        return mb_substr($id, 0, 247) . '…';
    }
}
