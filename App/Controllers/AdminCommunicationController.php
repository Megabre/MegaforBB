<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MailTemplate;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\MailService;
use App\Services\MailTemplateService;

/**
 * Admin: İletişim — Mesaj gönder, toplu mesaj, mesaj şablonları, mail şablonları.
 */
class AdminCommunicationController extends AdminController
{
    private const CSRF_TOKEN = 'admin_communication';

    public function sendForm(): string
    {
        $bag = $this->app->session()->getFlashBag();
        return $this->view('communication/send', [
            'pageTitle' => lang('admin.communication.send_mail_title'),
            'flashError' => $bag->get('error'),
            'flashSuccess' => $bag->get('success'),
            'placeholders' => MailTemplateService::getAvailablePlaceholders(),
        ]);
    }

    public function sendPost(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $redirectUrl = core_url($adminPath . '/communication/send');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $recipientType = (string)($_POST['recipient_type'] ?? 'email');
        $subject = trim((string)($_POST['subject'] ?? ''));
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));

        if ($subject === '' || $bodyHtml === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.subject_and_body_required'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $toEmail = '';
        $replace = $this->getSitePlaceholders();
        if ($recipientType === 'user') {
            $userIdentifier = trim((string)($_POST['user_identifier'] ?? ''));
            $user = $userIdentifier !== ''
                ? User::where('username', $userIdentifier)->orWhere('email', $userIdentifier)->first()
                : null;
            if (!$user || !$user->email) {
                $this->app->session()->getFlashBag()->add('error', lang('admin.communication.user_not_found_or_no_email'));
                header('Location: ' . $redirectUrl, true, 302);
                exit;
            }
            $toEmail = $user->email;
            $replace['username'] = $user->username;
            $replace['name'] = $user->username;
            $replace['email'] = $user->email ?? '';
        } else {
            $toEmail = trim((string)($_POST['email'] ?? ''));
            if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $this->app->session()->getFlashBag()->add('error', lang('admin.communication.invalid_email'));
                header('Location: ' . $redirectUrl, true, 302);
                exit;
            }
            $replace['email'] = $toEmail;
        }

        $subject = MailTemplateService::replaceInContent($subject, $replace);
        $bodyHtml = MailTemplateService::replaceInContent($bodyHtml, $replace);

        $mailer = new MailService($this->app);
        $sent = $mailer->send($toEmail, $subject, $bodyHtml, strip_tags($bodyHtml));
        if ($sent) {
            $this->app->session()->getFlashBag()->add('success', lang('admin.communication.message_sent'));
        } else {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.message_send_failed') . ' ' . $mailer->getLastError());
        }
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    public function bulkForm(): string
    {
        $bag = $this->app->session()->getFlashBag();
        $templates = MessageTemplate::orderBy('name')->get(['id', 'name', 'subject', 'body_html'])->all();
        return $this->view('communication/bulk', [
            'pageTitle' => lang('admin.communication.bulk_message_title'),
            'templates' => $templates,
            'flashError' => $bag->get('error'),
            'flashSuccess' => $bag->get('success'),
            'placeholders' => MailTemplateService::getAvailablePlaceholders(),
        ]);
    }

    public function bulkPost(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $redirectUrl = core_url($adminPath . '/communication/bulk');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $target = (string)($_POST['target'] ?? 'selected');
        $subject = trim((string)($_POST['subject'] ?? ''));
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));

        if ($subject === '' || $bodyHtml === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.subject_and_body_required'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $userIds = [];
        if ($target === 'all') {
            $userIds = User::whereNotNull('email')->where('email', '!=', '')->whereNull('closed_at')->pluck('id')->all();
        } else {
            $ids = (string)($_POST['user_ids'] ?? '');
            $userIds = array_filter(array_map('intval', preg_split('/[\s,]+/', $ids, -1, PREG_SPLIT_NO_EMPTY)));
        }

        $users = User::whereIn('id', $userIds)->get(['id', 'email', 'username']);
        $sent = 0;
        $failed = 0;
        $mailer = new MailService($this->app);
        $baseReplace = $this->getSitePlaceholders();
        foreach ($users as $user) {
            if (!$user->email) {
                continue;
            }
            $replace = $baseReplace;
            $replace['username'] = $user->username;
            $replace['name'] = $user->username;
            $replace['email'] = $user->email ?? '';
            $subj = MailTemplateService::replaceInContent($subject, $replace);
            $body = MailTemplateService::replaceInContent($bodyHtml, $replace);
            if ($mailer->send($user->email, $subj, $body, strip_tags($body))) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($sent > 0) {
            $this->app->session()->getFlashBag()->add('success', lang('admin.communication.bulk_sent', ['sent' => $sent]));
        }
        if ($failed > 0) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.bulk_failed', ['failed' => $failed]));
        }
        if ($sent === 0 && $failed === 0) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.no_recipients'));
        }
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    /**
     * Toplu mail: virgülle ayrılmış e-posta listesi, parçalı gönderim (1 / 5 / 50).
     */
    public function bulkMailForm(): string
    {
        $bag = $this->app->session()->getFlashBag();
        $templates = MessageTemplate::orderBy('name')->get(['id', 'name', 'subject', 'body_html'])->all();
        return $this->view('communication/bulk_mail', [
            'pageTitle' => lang('admin.communication.bulk_mail_title'),
            'templates' => $templates,
            'flashError' => $bag->get('error'),
            'flashSuccess' => $bag->get('success'),
            'flashInfo' => $bag->get('info'),
            'placeholders' => MailTemplateService::getAvailablePlaceholders(),
        ]);
    }

    public function bulkMailPost(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $redirectUrl = core_url($adminPath . '/communication/bulk-mail');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $emailsRaw = trim((string)($_POST['emails'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));
        $sendMode = (string)($_POST['send_mode'] ?? '1');

        if ($subject === '' || $bodyHtml === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.subject_and_body_required'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $emails = array_filter(array_map('trim', preg_split('/[\s,;]+/', $emailsRaw, -1, PREG_SPLIT_NO_EMPTY)));
        $emails = array_values(array_unique(array_filter($emails, static function (string $e): bool {
            return filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
        })));

        $invalidCount = count(array_filter(array_map('trim', preg_split('/[\s,;]+/', $emailsRaw, -1, PREG_SPLIT_NO_EMPTY)))) - count($emails);
        if ($invalidCount > 0) {
            $this->app->session()->getFlashBag()->add('info', lang('admin.communication.invalid_emails'));
        }

        if ($emails === []) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.no_recipients'));
            header('Location: ' . $redirectUrl, true, 302);
            exit;
        }

        $chunkSize = 1;
        $sleepSeconds = 1;
        if ($sendMode === '5') {
            $chunkSize = 5;
            $sleepSeconds = 2;
        } elseif ($sendMode === '50') {
            $chunkSize = 50;
            $sleepSeconds = 5;
        }

        $replace = $this->getSitePlaceholders();
        $mailer = new MailService($this->app);
        $sent = 0;
        $failed = 0;
        $chunks = array_chunk($emails, $chunkSize);

        foreach ($chunks as $index => $chunk) {
            if ($index > 0) {
                sleep($sleepSeconds);
            }
            foreach ($chunk as $email) {
                $replace['email'] = $email;
                $subj = MailTemplateService::replaceInContent($subject, $replace);
                $body = MailTemplateService::replaceInContent($bodyHtml, $replace);
                if ($mailer->send($email, $subj, $body, strip_tags($body))) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        if ($sent > 0) {
            $this->app->session()->getFlashBag()->add('success', lang('admin.communication.bulk_mail_sent', ['sent' => $sent]));
        }
        if ($failed > 0) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.bulk_mail_failed', ['failed' => $failed]));
        }
        header('Location: ' . $redirectUrl, true, 302);
        exit;
    }

    public function messageTemplatesIndex(): string
    {
        $templates = MessageTemplate::orderBy('name')->get()->all();
        return $this->view('communication/message_templates_index', [
            'pageTitle' => lang('admin.communication.message_templates_title'),
            'templates' => $templates,
        ]);
    }

    public function messageTemplateCreate(): string
    {
        return $this->view('communication/message_template_form', [
            'pageTitle' => lang('admin.communication.message_template_new'),
            'template' => null,
            'placeholders' => MailTemplateService::getAvailablePlaceholders(),
        ]);
    }

    public function messageTemplateStore(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
            exit;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));
        if ($name === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.template_name_required'));
            header('Location: ' . core_url($adminPath . '/communication/message-templates/create'), true, 302);
            exit;
        }
        MessageTemplate::create(['name' => $name, 'subject' => $subject, 'body_html' => $bodyHtml]);
        $this->app->session()->getFlashBag()->add('success', lang('admin.communication.template_created'));
        header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
        exit;
    }

    public function messageTemplateEdit(string $id): string
    {
        $template = MessageTemplate::find($id);
        if (!$template) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.template_not_found'));
            header('Location: ' . core_url(env('ADMIN_PATH', 'admin') . '/communication/message-templates'), true, 302);
            exit;
        }
        return $this->view('communication/message_template_form', [
            'pageTitle' => lang('admin.communication.message_template_edit'),
            'template' => $template,
            'placeholders' => MailTemplateService::getAvailablePlaceholders(),
        ]);
    }

    public function messageTemplateUpdate(string $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
            exit;
        }
        $template = MessageTemplate::find($id);
        if (!$template) {
            header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
            exit;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));
        if ($name === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.template_name_required'));
            header('Location: ' . core_url($adminPath . '/communication/message-templates/edit/' . $id), true, 302);
            exit;
        }
        $template->update(['name' => $name, 'subject' => $subject, 'body_html' => $bodyHtml]);
        $this->app->session()->getFlashBag()->add('success', lang('admin.communication.template_updated'));
        header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
        exit;
    }

    public function messageTemplateDelete(string $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
            exit;
        }
        MessageTemplate::where('id', $id)->delete();
        $this->app->session()->getFlashBag()->add('success', lang('admin.communication.template_deleted'));
        header('Location: ' . core_url($adminPath . '/communication/message-templates'), true, 302);
        exit;
    }

    public function mailTemplatesIndex(): string
    {
        if (MailTemplate::count() === 0) {
            $defaults = [
                ['template_key' => 'email_verification', 'name' => 'E-posta doğrulama'],
                ['template_key' => 'password_reset', 'name' => 'Şifre sıfırlama'],
                ['template_key' => 'login_code', 'name' => 'Giriş doğrulama kodu'],
                ['template_key' => 'contact_reply', 'name' => 'İletişim formu yanıtı'],
            ];
            foreach ($defaults as $row) {
                MailTemplate::create(array_merge($row, ['subject' => '', 'body_html' => '', 'body_text' => null]));
            }
        }
        $templates = MailTemplate::orderBy('template_key')->get()->all();
        return $this->view('communication/mail_templates_index', [
            'pageTitle' => lang('admin.communication.mail_templates_title'),
            'templates' => $templates,
        ]);
    }

    public function mailTemplateEdit(string $id): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $template = MailTemplate::find($id);
        if (!$template) {
            $this->app->session()->getFlashBag()->add('error', lang('admin.communication.template_not_found'));
            header('Location: ' . core_url($adminPath . '/communication/mail-templates'), true, 302);
            exit;
        }
        $bag = $this->app->session()->getFlashBag();
        return $this->view('communication/mail_template_form', [
            'pageTitle' => lang('admin.communication.mail_template_edit') . ': ' . $template->name,
            'template' => $template,
            'flashError' => $bag->get('error'),
            'flashSuccess' => $bag->get('success'),
            'placeholders' => MailTemplateService::getAvailablePlaceholders(),
        ]);
    }

    public function mailTemplateUpdate(string $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('error', core__('common.invalid_csrf'));
            header('Location: ' . core_url($adminPath . '/communication/mail-templates'), true, 302);
            exit;
        }
        $template = MailTemplate::find($id);
        if (!$template) {
            header('Location: ' . core_url($adminPath . '/communication/mail-templates'), true, 302);
            exit;
        }
        $subject = trim((string)($_POST['subject'] ?? ''));
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));
        $bodyText = trim((string)($_POST['body_text'] ?? ''));
        $template->update(['subject' => $subject, 'body_html' => $bodyHtml, 'body_text' => $bodyText]);
        $this->app->session()->getFlashBag()->add('success', lang('admin.communication.mail_template_updated'));
        header('Location: ' . core_url($adminPath . '/communication/mail-templates'), true, 302);
        exit;
    }

    /** Site adı ve URL; mesaj/mail etiketleri için kullanılır. */
    private function getSitePlaceholders(): array
    {
        $siteName = (string) $this->app->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB');
        $website = rtrim((string) core_config('app.url', ''), '/');
        if ($website === '' && function_exists('full_site_url')) {
            $website = rtrim(full_site_url(''), '/');
        }
        if ($website === '') {
            $scheme = \App\Services\SecurityService::isHttpsRequest() ? 'https' : 'http';
            $website = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        return [
            'site_name' => $siteName,
            'website_name' => $siteName,
            'website' => $website,
        ];
    }
}
