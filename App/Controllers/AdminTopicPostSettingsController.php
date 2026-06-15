<?php

declare(strict_types=1);

namespace App\Controllers;

class AdminTopicPostSettingsController extends AdminController
{
    private const GROUP = 'topic_post';

    public function index(): string
    {
        $data = [
            'max_post_length' => (int) $this->getSetting('max_post_length', '0'),
            'max_profile_message_length' => (int) $this->getSetting('max_profile_message_length', '0'),
            'max_topic_title_length' => (int) $this->getSetting('max_topic_title_length', '200'),
            'edit_timeout_minutes' => (int) $this->getSetting('edit_timeout_minutes', '0'),
            'posts_per_page' => (int) $this->getSetting('posts_per_page', '15'),
            'topics_per_page' => (int) $this->getSetting('topics_per_page', '20'),
            'min_time_between_posts' => (int) $this->getSetting('min_time_between_posts', '0'),
            'min_time_between_topics' => (int) $this->getSetting('min_time_between_topics', '0'),
            'max_poll_options' => (int) $this->getSetting('max_poll_options', '10'),
            'antibump_enabled' => $this->getSetting('antibump_enabled', '1') === '1',
            'antibump_seconds' => (int) $this->getSetting('antibump_seconds', '60'),
            'lightbox_all_images_enabled' => $this->getSetting('lightbox_all_images_enabled', '1') === '1',
            'show_signatures_to_guests' => $this->getSetting('show_signatures_to_guests', '1') === '1',
            'hide_content_from_guests' => $this->getSetting('hide_content_from_guests', '0') === '1',
            'enable_inline_quotes' => $this->getSetting('enable_inline_quotes', '1') === '1',
            'topic_post_scrubber_enabled' => $this->getSetting('topic_post_scrubber_enabled', '1') === '1',
            'post_editor' => $this->normalizePostEditor($this->getSetting('post_editor', 'toast_ui')),
            'post_editor' => $this->getSetting('post_editor', 'toast_ui') === 'tinymce' ? 'tinymce' : 'toast_ui',
        ];
        return $this->view('topic_post_settings/index', [
            'pageTitle' => lang('admin.topic_post.title'),
            'settings' => $data,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid('admin_topic_post_settings', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/topic-post-settings'));
            return;
        }

        $groupForum = 'forum';
        $groupTopicPost = self::GROUP;

        $this->setSetting('antibump_enabled', isset($_POST['antibump_enabled']) && $_POST['antibump_enabled'] === '1' ? '1' : '0', $groupForum);
        $antibumpSec = max(0, (int) ($_POST['antibump_seconds'] ?? 60));
        $this->setSetting('antibump_seconds', (string) $antibumpSec, $groupForum);

        $intKeys = [
            'max_post_length' => $groupTopicPost,
            'max_profile_message_length' => $groupTopicPost,
            'max_topic_title_length' => $groupTopicPost,
            'edit_timeout_minutes' => $groupTopicPost,
            'posts_per_page' => $groupForum,
            'topics_per_page' => $groupForum,
            'min_time_between_posts' => $groupTopicPost,
            'min_time_between_topics' => $groupTopicPost,
            'max_poll_options' => $groupTopicPost,
        ];
        foreach ($intKeys as $key => $group) {
            $v = isset($_POST[$key]) ? (string) (int) $_POST[$key] : '0';
            $this->setSetting($key, $v, $group);
        }

        $boolKeys = [
            'lightbox_all_images_enabled',
            'show_signatures_to_guests',
            'hide_content_from_guests',
            'enable_inline_quotes',
            'topic_post_scrubber_enabled',
        ];
        foreach ($boolKeys as $key) {
            $v = isset($_POST[$key]) && $_POST[$key] === '1' ? '1' : '0';
            $this->setSetting($key, $v, $groupTopicPost);
        }
        $postEditor = $this->normalizePostEditor((string) ($_POST['post_editor'] ?? 'toast_ui'));
        $this->setSetting('post_editor', $postEditor, $groupTopicPost);


        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/topic-post-settings'));
    }

    private function normalizePostEditor(string $value): string
    {
        return in_array($value, ['toast_ui', 'tinymce', 'ckeditor'], true) ? $value : 'toast_ui';
    }
}
