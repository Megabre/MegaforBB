<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Invitation;

class AdminInvitationController extends AdminController
{
    private const CSRF_TOKEN = 'admin_invitation';

    public function index(): string
    {
        $invitations = Invitation::with(['inviter:id,username', 'newUser:id,username'])
            ->orderByDesc('id')
            ->get();
        $adminPath = env('ADMIN_PATH', 'admin');
        $bag = $this->app->session()->getFlashBag();
        $flashSuccess = $bag->get('invitation_success');
        $flashError = $bag->get('invitation_error');
        return $this->view('invitations/index', [
            'pageTitle' => lang('invitation.page_title'),
            'invitations' => $invitations,
            'adminPath' => $adminPath,
            'flashSuccess' => is_array($flashSuccess) ? ($flashSuccess[0] ?? '') : (string) ($flashSuccess ?? ''),
            'flashError' => is_array($flashError) ? ($flashError[0] ?? '') : (string) ($flashError ?? ''),
        ]);
    }

    /**
     * Admin panelinden davetiye üret. Yetkili (staff) kullanıcılar kota sınırı olmadan sınırsız üretebilir.
     */
    public function generate(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $currentUser = $this->app->auth()->user();

        $code = strtoupper(\Illuminate\Support\Str::random(10));
        $currentUser->invitations()->create([
            'code' => $code,
            'email' => null,
            'expires_at' => now()->addDays(7),
        ]);

        $this->app->session()->getFlashBag()->add('invitation_success', lang('invitation.generate_success', ['code' => $code]));
        header('Location: ' . core_url($adminPath . '/invitations'), true, 302);
        exit;
    }

    public function revoke(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/invitations'));
            return;
        }
        $invitation = Invitation::find((int) $id);
        if ($invitation && $invitation->used_at === null) {
            $invitation->delete();
            $this->app->session()->getFlashBag()->add('invitation_success', lang('invitation.revoked'));
        }
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/invitations'));
    }
}
