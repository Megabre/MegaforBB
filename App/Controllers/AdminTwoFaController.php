<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Forecor\Core\Application;

/**
 * Admin 2FA: Panele giriş için soru-cevap doğrulaması.
 * - GET /admin/twofa → form (soru + cevap)
 * - POST /admin/twofa → doğrula, session set et, admin'e yönlendir
 * - GET /admin/security/twofa → 2FA ayarları (soru/cevap belirle)
 * - POST /admin/security/twofa → ayarları kaydet
 */
class AdminTwoFaController extends AdminController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /** 2FA doğrulama formu (panele girmeden önce) */
    public function index(): string
    {
        $user = $this->app->auth()->user();
        if (!$user || empty($user->admin_twofa_question)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin')));
            return '';
        }
        $adminPath = env('ADMIN_PATH', 'admin');
        return $this->view('twofa/form', [
            'pageTitle' => lang('admin.twofa.page_title'),
            'question' => $user->admin_twofa_question,
            'adminPath' => $adminPath,
        ]);
    }

    /** 2FA cevabını doğrula */
    public function verify(): string
    {
        $user = $this->app->auth()->user();
        if (!$user || empty($user->admin_twofa_question)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin')));
            return '';
        }
        if (!core_csrf_valid('admin_twofa', (string) ($_POST['_token'] ?? ''))) {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('twofa_error', lang('common.invalid_csrf'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/twofa'));
            return '';
        }
        $answer = trim((string) ($_POST['answer'] ?? ''));
        $hash = $user->admin_twofa_answer_hash ?? '';
        if ($hash === '' || !password_verify($answer, $hash)) {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('twofa_error', lang('admin.twofa.wrong_answer'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/twofa'));
            return '';
        }
        \Forecor\Core\SessionManager::get()->set('admin_2fa_verified_user_id', (int) $user->id);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin')));
        return '';
    }

    /** 2FA ayarları sayfası (soru + cevap belirle) */
    public function settingsForm(): string
    {
        $user = $this->app->auth()->user();
        return $this->view('security/twofa', [
            'pageTitle' => lang('admin.twofa.settings_title'),
            'question' => $user->admin_twofa_question ?? '',
            'hasAnswer' => !empty($user->admin_twofa_answer_hash),
        ]);
    }

    /** 2FA ayarlarını kaydet */
    public function settingsUpdate(): string
    {
        if (!core_csrf_valid('admin_security', (string) ($_POST['_token'] ?? ''))) {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('common.invalid_csrf'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security/twofa'));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security/twofa'));
            return '';
        }
        $question = trim((string) ($_POST['admin_twofa_question'] ?? ''));
        $answer = trim((string) ($_POST['admin_twofa_answer'] ?? ''));
        $disable = isset($_POST['admin_twofa_disable']) && $_POST['admin_twofa_disable'] === '1';

        $adminPath = env('ADMIN_PATH', 'admin');
        if ($disable) {
            User::where('id', $user->id)->update([
                'admin_twofa_question' => null,
                'admin_twofa_answer_hash' => null,
            ]);
            \Forecor\Core\SessionManager::get()->remove('admin_2fa_verified_user_id');
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('success', lang('admin.twofa.disabled'));
            $this->redirect(core_url($adminPath . '/security/twofa'));
            return '';
        }
        if ($question === '') {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.twofa.question_required'));
            $this->redirect(core_url($adminPath . '/security/twofa'));
            return '';
        }
        $hasAnswer = !empty($user->admin_twofa_answer_hash);
        if ($answer === '' && !$hasAnswer) {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.twofa.answer_required'));
            $this->redirect(core_url($adminPath . '/security/twofa'));
            return '';
        }
        $data = ['admin_twofa_question' => $question];
        if ($answer !== '') {
            $data['admin_twofa_answer_hash'] = password_hash($answer, PASSWORD_DEFAULT);
        }
        User::where('id', $user->id)->update($data);
        \Forecor\Core\SessionManager::get()->set('admin_2fa_verified_user_id', (int) $user->id);
        \Forecor\Core\SessionManager::get()->getFlashBag()->set('success', lang('admin.twofa.saved'));
        $this->redirect(core_url($adminPath . '/security/twofa'));
        return '';
    }
}
