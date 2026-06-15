<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Services\MailService;

/**
 * Admin: İletişim mesajlarını listeleme, okuma, cevaplama (SMTP) ve silme.
 */
class AdminContactController extends AdminController
{
    private const CSRF_TOKEN = 'admin_contact';

    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $total = (int) ContactMessage::count();
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;

        $messages = ContactMessage::orderByDesc('id')->offset(($page - 1) * $perPage)->limit($perPage)->get(['id', 'name', 'email', 'subject', 'is_read', 'created_at'])->all();

        return $this->view('contact/index', [
            'pageTitle' => lang('admin.contact.title'),
            'messages' => $messages,
            'page' => $page,
            'totalPages' => $totalPages,
            'adminPath' => $adminPath
        ]);
    }

    public function show(string $id): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');

        $message = ContactMessage::find($id);

        if (!$message) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.contact.message_not_found'));
            header('Location: ' . core_url($adminPath . '/contact'), true, 302);
            exit;
        }

        if ((int)$message->is_read === 0) {
            $message->update(['is_read' => 1]);
            $message->is_read = 1;
        }

        $replies = [];
        try {
            $replies = ContactMessageReply::with('repliedByUser')
                ->where('contact_message_id', $id)
                ->orderBy('created_at')
                ->get()
                ->map(fn ($r) => (object) [
                    'id' => $r->id,
                    'reply_body' => $r->reply_body,
                    'replied_by_user_id' => $r->replied_by_user_id,
                    'email_sent' => $r->email_sent,
                    'created_at' => $r->created_at,
                    'replied_by_username' => $r->repliedByUser ? $r->repliedByUser->username : null,
                ])
                ->all();
        } catch (\Throwable $e) {
        }

        $flashError = $this->app->session()->getFlashBag()->get('error');
        $flashSuccess = $this->app->session()->getFlashBag()->get('success');
        return $this->view('contact/show', [
            'pageTitle' => lang('admin.contact.detail_title'),
            'message' => $message,
            'replies' => $replies,
            'adminPath' => $adminPath,
            'flashError' => $flashError,
            'flashSuccess' => $flashSuccess,
        ]);
    }

    /**
     * Sends reply to contact message via SMTP.
     */
    public function reply(string $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $redirectUrl = core_url($adminPath . '/contact/show/' . $id);

        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $message = ContactMessage::find($id);

        if (!$message) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.contact.message_not_found'));
            header('Location: ' . core_url($adminPath . '/contact'), true, 302);
            exit;
        }

        $replyBody = trim((string)($_POST['reply_body'] ?? ''));
        if ($replyBody === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.contact.reply_empty'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $to = $message->email;
        $siteName = (string) $this->app->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB');
        $replace = [
            'name' => htmlspecialchars($message->name, ENT_QUOTES, 'UTF-8'),
            'reply_body' => nl2br(htmlspecialchars($replyBody, ENT_QUOTES, 'UTF-8')),
            'site_name' => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
        ];
        $templateService = new \App\Services\MailTemplateService($this->app);
        $subject = $templateService->getSubject('contact_reply', $replace);
        $bodyHtml = $templateService->getBodyHtml('contact_reply', $replace);
        if ($subject === null) {
            $subject = 'Re: ' . $message->subject;
        }
        if ($bodyHtml === null) {
            $bodyHtml = '<p>' . lang('admin.contact.email_hello', ['name' => $replace['name']]) . '</p>';
            $bodyHtml .= '<p>' . lang('admin.contact.email_reply_intro') . '</p>';
            $bodyHtml .= '<div style="margin:1em 0;padding:1em;background:#f5f5f5;border-radius:6px;">' . $replace['reply_body'] . '</div>';
            $bodyHtml .= '<p>' . lang('admin.contact.email_signoff') . '</p>';
        }
        $bodyText = strip_tags($replyBody);

        $mailer = new MailService($this->app);
        $sent = $mailer->send($to, $subject, $bodyHtml, $bodyText);

        $userId = (int) $this->app->auth()->user()->id;
        $saved = false;
        try {
            ContactMessageReply::create([
                'contact_message_id' => (int) $id,
                'reply_body' => $replyBody,
                'replied_by_user_id' => $userId,
                'email_sent' => $sent,
                'created_at' => \now(),
            ]);
            $saved = true;
        } catch (\Throwable $e) {
            error_log('Contact reply save error: ' . $e->getMessage());
        }

        if ($sent && $saved) {
            $this->app->session()->getFlashBag()->add('success', lang('admin.contact.reply_sent'));
        } elseif ($sent && !$saved) {
            $this->app->session()->getFlashBag()->add('success', lang('admin.contact.reply_sent_no_log'));
        } elseif (!$sent && $saved) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.contact.email_failed'));
        } else {
            $this->app->session()->getFlashBag()->add('error', lang('admin.contact.email_failed_no_log'));
        }

        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    public function delete(string $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . core_url($adminPath . '/contact'), true, 302);
            exit;
        }

        try {
            ContactMessage::where('id', $id)->delete();
            $this->app->session()->getFlashBag()->add('success', lang('admin.contact.message_deleted'));
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.contact.message_delete_failed'));
        }

        header('Location: ' . core_url($adminPath . '/contact'), true, 302);
        exit;
    }
}
