<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\UserCustomField;
use App\Models\UserFieldDefinition;
use App\Models\UserPreference;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Logged-in user profile edit (panel).
 */
class ProfileController extends BaseController
{
    public function editForm(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $columns = ['id', 'username', 'email', 'avatar_path', 'cover_photo_path', 'location', 'website', 'bio'];
        $schema = DB::connection()->getSchemaBuilder();
        if ($schema->hasColumn('users', 'first_name')) {
            $columns = array_merge($columns, ['first_name', 'last_name', 'show_name', 'birthday']);
        }
        if ($schema->hasColumn('users', 'signature')) {
            $columns[] = 'signature';
        }
        $profile = User::select($columns)->find($user->id);
        if (!$profile) {
            $this->redirect(core_url(''));
            return '';
        }
        $customDefinitions = $this->getProfileCustomFieldDefinitions();
        $customValues = $this->getUserCustomFieldValues((int)$user->id);
        $error = $this->app->session()->getFlashBag()->get('profile_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $success = $this->app->session()->getFlashBag()->get('profile_success');
        $success = is_array($success) ? ($success[0] ?? '') : $success;
        return $this->layout('profile/edit', [
            'profile' => $profile,
            'customDefinitions' => $customDefinitions,
            'customValues' => $customValues,
            'pageTitle' => lang('profile.edit_page_title'),
            'error' => $error,
            'success' => $success,
        ], false);
    }

    public function update(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('profile_edit', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('profile_error', lang('profile.invalid_request'));
            $this->redirect(core_url('profile/edit'));
            return '';
        }
        $location = trim((string)($_POST['location'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $maxBioLen = (int) $this->getSetting('max_profile_message_length', '0');
        if ($maxBioLen > 0 && mb_strlen($bio) > $maxBioLen) {
            $this->app->session()->getFlashBag()->add('profile_error', lang('profile.bio_max', ['max' => $maxBioLen]));
            $this->redirect(core_url('profile/edit'));
            return '';
        }
        $signature = trim((string)($_POST['signature'] ?? ''));
        $censorship = $this->app->censorship();
        if ($censorship->isCensorshipEnabled() && $censorship->applyToSignatures() && $signature !== '') {
            $sigCheck = $censorship->checkContent($signature);
            if (!$sigCheck['allowed']) {
                $this->app->session()->getFlashBag()->add('profile_error', lang('censorship.content_blocked'));
                $this->redirect(core_url('profile/edit'));
                return '';
            }
            $signature = $sigCheck['filtered_text'];
        }
        $signature = $signature !== '' ? core_sanitize_signature($signature) : '';
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $showName = isset($_POST['show_name']) && $_POST['show_name'] === '1' ? 1 : 0;
        $birthday = trim((string)($_POST['birthday'] ?? ''));
        $birthdayVal = null;
        if ($birthday !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
            $birthdayVal = $birthday;
        }
        $data = [
            'location' => $location ?: null,
            'website' => $website ?: null,
            'bio' => $bio ?: null,
            'updated_at' => \now(),
        ];
        $schema = DB::connection()->getSchemaBuilder();
        if ($schema->hasColumn('users', 'signature')) {
            $data['signature'] = $signature ?: null;
        }
        if ($schema->hasColumn('users', 'first_name')) {
            $data['first_name'] = $firstName ?: null;
            $data['last_name'] = $lastName ?: null;
            $data['show_name'] = $showName;
            $data['birthday'] = $birthdayVal;
        }
        User::where('id', $user->id)->update($data);
        $this->saveUserCustomFields((int)$user->id);
        $this->app->session()->getFlashBag()->add('profile_success', lang('profile.success_updated'));
        $this->redirect(core_url('member/' . rawurlencode($user->username)));
        return '';
    }

    private function getProfileCustomFieldDefinitions(): array
    {
        try {
            return UserFieldDefinition::where('show_on_profile', 1)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'field_key', 'field_type', 'field_options', 'is_required'])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getUserCustomFieldValues(int $userId): array
    {
        try {
            return UserCustomField::where('user_id', $userId)->pluck('field_value', 'field_key')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function saveUserCustomFields(int $userId): void
    {
        try {
            $defs = UserFieldDefinition::where('show_on_profile', 1)->get(['id', 'field_key']);
            foreach ($defs as $def) {
                $value = trim((string)($_POST['custom_' . $def->field_key] ?? ''));
                UserCustomField::updateOrCreate(
                    ['user_id' => $userId, 'field_key' => $def->field_key],
                    ['field_value' => $value ?: null]
                );
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function passwordForm(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $error = $this->app->session()->getFlashBag()->get('password_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $success = $this->app->session()->getFlashBag()->get('password_success');
        $success = is_array($success) ? ($success[0] ?? '') : $success;
        return $this->layout('profile/password', [
            'pageTitle' => lang('profile.password_title'),
            'error' => $error,
            'success' => $success,
        ], false);
    }

    public function passwordUpdate(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('profile_password', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('password_error', lang('profile.password_invalid_request'));
            $this->redirect(core_url('profile/password'));
            return '';
        }
        $current = (string)($_POST['current_password'] ?? '');
        $newPass = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');
        if ($newPass !== $confirm || strlen($newPass) < 6) {
            $this->app->session()->getFlashBag()->add('password_error', lang('profile.password_min_match'));
            $this->redirect(core_url('profile/password'));
            return '';
        }
        $userModel = User::find($user->id);
        if (!$userModel || !password_verify($current, $userModel->password_hash ?? '')) {
            $this->app->session()->getFlashBag()->add('password_error', lang('profile.password_current_wrong'));
            $this->redirect(core_url('profile/password'));
            return '';
        }
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $userModel->update(['password_hash' => $hash, 'updated_at' => \now()]);
        $this->app->session()->getFlashBag()->add('password_success', lang('profile.password_success'));
        $this->redirect(core_url('profile/password'));
        return '';
    }

    public function coverUpload(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => lang('upload.login_required')]);
            return '';
        }
        $file = $_FILES['cover'] ?? $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => lang('profile.upload_failed')]);
            return '';
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = $this->detectMimeType((string) $file['tmp_name']);
        if (!isset($allowed[$mime]) || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => lang('profile.invalid_file_or_size_5mb')]);
            return '';
        }
        $toUpload = $file['tmp_name'];
        $ext = $allowed[$mime];
        if (\App\Services\ImageProcessor::isProcessableImage($mime)) {
            $processed = \App\Services\ImageProcessor::processToWebp($file['tmp_name']);
            if ($processed !== null) {
                $toUpload = $processed;
                $ext = 'webp';
            }
        }
        $subDir = date('Y') . '/' . date('m');
        $name = 'u' . $user->id . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $relative = 'uploads/covers/' . $subDir . '/' . $name;
        $storage = $this->storage();
        if (!$storage->putFile($relative, $toUpload)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => lang('profile.save_failed')]);
            return '';
        }
        if ($toUpload !== $file['tmp_name'] && is_file($toUpload)) {
            @unlink($toUpload);
        }
        $urlPath = $storage->url($relative);
        User::where('id', $user->id)->update(['cover_photo_path' => $relative, 'updated_at' => \now()]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'url' => $urlPath, 'path' => $relative]);
        return '';
    }

    public function avatarUpload(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => lang('upload.login_required')]);
            return '';
        }
        $file = $_FILES['avatar'] ?? $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => lang('profile.upload_failed')]);
            return '';
        }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = $this->detectMimeType((string) $file['tmp_name']);
        if (!isset($allowed[$mime]) || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => lang('profile.invalid_file_or_size_2mb')]);
            return '';
        }
        $toUpload = $file['tmp_name'];
        $ext = $allowed[$mime];
        if (\App\Services\ImageProcessor::isProcessableImage($mime)) {
            $processed = \App\Services\ImageProcessor::processToWebp($file['tmp_name']);
            if ($processed !== null) {
                $toUpload = $processed;
                $ext = 'webp';
            }
        }
        $subDir = date('Y') . '/' . date('m');
        $name = 'u' . $user->id . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $relative = 'uploads/avatars/' . $subDir . '/' . $name;
        $storage = $this->storage();
        if (!$storage->putFile($relative, $toUpload)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => lang('profile.save_failed')]);
            return '';
        }
        if ($toUpload !== $file['tmp_name'] && is_file($toUpload)) {
            @unlink($toUpload);
        }
        $urlPath = $storage->url($relative);
        User::where('id', $user->id)->update(['avatar_path' => $relative, 'updated_at' => \now()]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'url' => $urlPath, 'path' => $relative]);
        return '';
    }

    /** Hesabı askıya al / kalıcı kapat sayfası */
    public function accountForm(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $error = $this->app->session()->getFlashBag()->get('account_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $success = $this->app->session()->getFlashBag()->get('account_success');
        $success = is_array($success) ? ($success[0] ?? '') : $success;
        return $this->layout('profile/account', [
            'pageTitle' => lang('profile.account_page_title'),
            'error' => $error,
            'success' => $success,
            'close_confirm_phrase' => lang('profile.close_confirm_phrase'),
        ], false);
    }

    /** Hesabı askıya al: şifre doğrula, is_suspended=1 yap, çıkış yap */
    public function suspendAccount(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('profile_account', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('account_error', lang('profile.invalid_request'));
            $this->redirect(core_url('profile/account'));
            return '';
        }
        $password = (string)($_POST['password'] ?? '');
        $userModel = User::find($user->id);
        if (!$userModel || !password_verify($password, $userModel->password_hash ?? '')) {
            $this->app->session()->getFlashBag()->add('account_error', lang('profile.password_current_wrong'));
            $this->redirect(core_url('profile/account'));
            return '';
        }
        User::where('id', $user->id)->update([
            'is_suspended' => 1,
            'suspended_at' => \now(),
            'updated_at' => \now(),
        ]);
        $this->app->auth()->logout();
        $this->app->session()->getFlashBag()->add('auth_success', lang('profile.suspend_success'));
        $this->redirect(core_url('login'));
        return '';
    }

    /** Hesabı kalıcı kapat: şifre + onay metni doğrula, closed_at yap, çıkış yap */
    public function closeAccount(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('profile_account', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('account_error', lang('profile.invalid_request'));
            $this->redirect(core_url('profile/account'));
            return '';
        }
        $password = (string)($_POST['password_close'] ?? '');
        $confirmPhrase = trim((string)($_POST['close_confirm'] ?? ''));
        $expectedPhrase = lang('profile.close_confirm_phrase');
        if ($confirmPhrase !== $expectedPhrase) {
            $this->app->session()->getFlashBag()->add('account_error', lang('profile.close_confirm_mismatch'));
            $this->redirect(core_url('profile/account'));
            return '';
        }
        $userModel = User::find($user->id);
        if (!$userModel || !password_verify($password, $userModel->password_hash ?? '')) {
            $this->app->session()->getFlashBag()->add('account_error', lang('profile.password_current_wrong'));
            $this->redirect(core_url('profile/account'));
            return '';
        }
        User::where('id', $user->id)->update([
            'closed_at' => \now(),
            'updated_at' => \now(),
        ]);
        $this->app->auth()->logout();
        $this->app->session()->getFlashBag()->add('auth_error', lang('profile.close_success'));
        $this->redirect(core_url('login'));
        return '';
    }

    public function preferencesForm(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $prefs = $this->getUserPreferences((int)$user->id);
        $error = $this->app->session()->getFlashBag()->get('preferences_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $success = $this->app->session()->getFlashBag()->get('preferences_success');
        $success = is_array($success) ? ($success[0] ?? '') : $success;
        return $this->layout('profile/preferences', [
            'prefs' => $prefs,
            'user' => $user,
            'pageTitle' => lang('profile.preferences_title'),
            'error' => $error,
            'success' => $success,
        ], false);
    }

    public function preferencesUpdate(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('profile_preferences', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('preferences_error', lang('profile.preferences_invalid_request'));
            $this->redirect(core_url('profile/preferences'));
            return '';
        }
        try {
            $keys = ['locale', 'timezone', 'email_newsletter', 'email_activity_summary', 'email_inactive_digest', 'email_on_dm',
                'follow_created_content', 'follow_created_email', 'follow_interacted_content', 'follow_interacted_email', 'show_signature',
                'show_online_status', 'show_activity', 'allow_profile_comments',
                'notif_followed_forum', 'notif_followed_topic_reply', 'notif_quote', 'notif_mention', 'notif_reaction', 'notif_profile_message',
                'notif_profile_mention', 'notif_profile_reaction', 'notif_dm_reaction', 'notif_new_follower', 'notif_new_reward'];
            $boolKeys = ['email_newsletter', 'email_activity_summary', 'email_inactive_digest', 'email_on_dm',
                'follow_created_content', 'follow_created_email', 'follow_interacted_content', 'follow_interacted_email', 'show_signature',
                'show_online_status', 'show_activity', 'allow_profile_comments',
                'notif_followed_forum', 'notif_followed_topic_reply', 'notif_quote', 'notif_mention', 'notif_reaction', 'notif_profile_message',
                'notif_profile_mention', 'notif_profile_reaction', 'notif_dm_reaction', 'notif_new_follower', 'notif_new_reward'];
            foreach ($keys as $key) {
                $value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
                if (in_array($key, $boolKeys, true)) {
                    $value = ($value === '1' || $value === 'on') ? '1' : '0';
                }
                $this->setUserPreference((int)$user->id, $key, $value);
            }
            if (isset($_POST['locale'])) {
                $newLocale = trim((string)$_POST['locale']);
                if (in_array($newLocale, ['tr', 'en'], true)) {
                    User::where('id', $user->id)->update(['locale' => $newLocale]);
                    \Forecor\Core\SessionManager::get()->set('locale', $newLocale);
                    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
                    setcookie('locale', $newLocale, time() + (365 * 24 * 3600), '/', '', $secure, true);
                }
            }
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('preferences_error', lang('profile.preferences_save_error'));
        }
        $this->app->session()->getFlashBag()->add('preferences_success', lang('profile.preferences_saved'));
        $this->redirect(core_url('profile/preferences'));
        return '';
    }

    private function getUserPreferences(int $userId): array
    {
        try {
            return UserPreference::where('user_id', $userId)->pluck('value', 'preference_key')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function setUserPreference(int $userId, string $key, string $value): void
    {
        try {
            UserPreference::updateOrCreate(
                ['user_id' => $userId, 'preference_key' => $key],
                ['value' => $value, 'updated_at' => \now()]
            );
        } catch (\Throwable $e) {
        }
    }

    private function detectMimeType(string $tmpPath): ?string
    {
        if (function_exists('finfo_open') && function_exists('finfo_file') && defined('FILEINFO_MIME_TYPE')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = @finfo_file($finfo, $tmpPath);
                @finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmpPath);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        if (function_exists('getimagesize')) {
            $info = @getimagesize($tmpPath);
            if (is_array($info) && isset($info['mime']) && is_string($info['mime']) && $info['mime'] !== '') {
                return $info['mime'];
            }
        }

        return null;
    }
}
