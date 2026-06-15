<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\VarnishService;

class VarnishPurgeListener
{
    private ?VarnishService $varnish = null;

    private function getVarnish(): VarnishService
    {
        if ($this->varnish === null) {
            $this->varnish = new VarnishService(app());
        }
        return $this->varnish;
    }

    public function onTopicCreated($event): void
    {
        $topic = $event->topic ?? null;
        if (!$topic) {
            return;
        }

        $service = $this->getVarnish();
        // Ana sayfayı ve forum listesini temizle
        $service->purge('/');
        $service->purge('/forum');

        if ($topic->forum_id) {
            // İlgili forumun sayfalarını BAN ile sil (örneğin /forum/1, /forum/1/page/2)
            $service->ban('^/forum/' . $topic->forum_id . '(-|/|$)');
        }
    }

    public function onTopicEdited($event): void
    {
        $topic = $event->topic ?? null;
        if (!$topic) {
            return;
        }

        $service = $this->getVarnish();
        $service->ban('^/topic/' . $topic->id . '(-|/|$)');

        $service->purge('/');
        $service->purge('/forum');
        if ($topic->forum_id) {
            $service->ban('^/forum/' . $topic->forum_id . '(-|/|$)');
        }
    }

    public function onTopicDeleted($event): void
    {
        $topic = $event->topic ?? null;
        if (!$topic) {
            return;
        }

        $service = $this->getVarnish();
        // Konuyu cache'den sil
        $service->ban('^/topic/' . $topic->id . '(-|/|$)');

        // Ana sayfaları güncelle
        $service->purge('/');
        $service->purge('/forum');
        if ($topic->forum_id) {
            $service->ban('^/forum/' . $topic->forum_id . '(-|/|$)');
        }
    }

    public function onPostCreated($event): void
    {
        $post = $event->post ?? null;
        if (!$post) {
            return;
        }

        $service = $this->getVarnish();
        if ($post->topic_id) {
            // Konudaki tüm sayfalamaları Ban'la
            $service->ban('^/topic/' . $post->topic_id . '(-|/|$)');
        }

        $service->purge('/');
        $service->purge('/forum');
        // Mesaj yeni atıldığı için forum listesindeki "Son Mesaj" sütunu güncellenmeli!
        if ($post->topic && $post->topic->forum_id) {
            $service->ban('^/forum/' . $post->topic->forum_id . '(-|/|$)');
        }
    }

    public function onPostDeleted($event): void
    {
        // Aynı şekilde post silindiğinde de topic önbelleği ve ana dizinler yenilenmelidir.
        $this->onPostCreated($event);
    }
}
