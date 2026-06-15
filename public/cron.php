<?php

declare(strict_types=1);

/**
 * MegaforBB Cron Job (Premium MVC)
 * Günlük sistem bakımı, istatistik güncellemeleri, zombi konular, eski bildirimler ve post_edits temizliği.
 * Tetikleme: ?token=... (Yönetim > Sistem Ayarları > cron_token ile eşleşmeli).
 */

$basePath = dirname(__DIR__);
if (!defined('MEGAFORBB_BASE_PATH')) {
    define('MEGAFORBB_BASE_PATH', $basePath);
}

require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'env.php';
require $basePath . DIRECTORY_SEPARATOR . 'Library' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$forecorSymmod = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'symmod' . DIRECTORY_SEPARATOR . 'symfony.php';
if (is_file($forecorSymmod)) {
    require $forecorSymmod;
}

$forecorLaramod = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'laramod' . DIRECTORY_SEPARATOR . 'laravel.php';
if (is_file($forecorLaramod)) {
    require $forecorLaramod;
}

require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'config.php';

date_default_timezone_set(core_config('app.timezone', 'UTC'));

use Illuminate\Database\Capsule\Manager as DB;

$expectedToken = '';
try {
    $expectedToken = (string) (DB::table('settings')->where('key', 'cron_token')->value('value') ?? '');
} catch (\Throwable $e) {
}

$providedToken = $_GET['token'] ?? '';

if (PHP_SAPI === 'cli' && isset($argv)) {
    foreach ($argv as $i => $arg) {
        if (strpos($arg, 'token=') === 0) {
            $providedToken = substr($arg, 6);
            break;
        }
        if (preg_match('/[?&]token=([^&\s]+)/', $arg, $m)) {
            $providedToken = trim($m[1]);
            break;
        }
        if ($i === 1 && $arg !== '' && strpos($arg, '=') === false) {
            $providedToken = $arg;
            break;
        }
    }
}

$isWeb = (PHP_SAPI !== 'cli');
if ($isWeb && ($expectedToken === '' || $providedToken !== $expectedToken)) {
    http_response_code(403);
    die("Cron Token Gecersiz veya Eksik.\n");
}
if (!$isWeb && $expectedToken !== '' && $providedToken !== $expectedToken) {
    http_response_code(403);
    die("Cron Token Gecersiz veya Eksik.\n");
}

$app = new \Forecor\Core\Application($basePath);
if (function_exists('app')) {
    app($app);
}

// Eklenti plugin.php → hooks (filters/actions) yüklenir; aksi halde cron.reports vb. çalışmaz.
$app->event();

$reports = [];

try {
    require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'publish_scheduled_topics.php';
    require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'rss_feed_import.php';

    $reports = $app->hooks()->applyFilters('cron.reports', $reports, $app);

    $forumIds = DB::table('forums')->pluck('id')->all();
    $totalRebuild = 0;

    foreach ($forumIds as $fId) {
        $tCount = DB::table('topics')->where('forum_id', $fId)->whereNull('deleted_at')->count();
        $pCount = DB::table('posts as p')
            ->join('topics as t', 't.id', '=', 'p.topic_id')
            ->where('t.forum_id', $fId)
            ->whereNull('p.deleted_at')
            ->whereNull('t.deleted_at')
            ->count();
        $lastPost = DB::table('posts as p')
            ->join('topics as t', 't.id', '=', 'p.topic_id')
            ->where('t.forum_id', $fId)
            ->whereNull('p.deleted_at')
            ->whereNull('t.deleted_at')
            ->orderByDesc('p.created_at')
            ->first(['p.topic_id as t_last_id', 'p.user_id as u_last_id']);

        DB::table('forums')->where('id', $fId)->update([
            'topic_count' => $tCount,
            'post_count' => $pCount,
            'last_post_id' => $lastPost->t_last_id ?? null,
            'last_post_user_id' => $lastPost->u_last_id ?? null,
            'last_post_at' => now(),
        ]);
        $totalRebuild++;
    }

    $globalTotals = (new \App\Services\LayoutDataService($app))->recalculateForumStatsTotals();
    $reports[] = '1. Forum Istatistikleri ('
        . $totalRebuild
        . ' forum) + genel sayaçlar: '
        . (int) $globalTotals['total_topics']
        . ' konu, '
        . (int) $globalTotals['total_posts']
        . ' mesaj, '
        . (int) $globalTotals['total_members']
        . ' üye (banlı hariç).';

    $deletedNotif = DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('created_at', '<', DB::raw('DATE_SUB(NOW(), INTERVAL 30 DAY)'))
        ->delete();
    $reports[] = "2. Eski/Okunmus Bildirimler ({$deletedNotif} adet) silindi.";

    $dismissalsToRemove = DB::table('announcement_dismissals as ad')
        ->leftJoin('announcements as a', 'a.id', '=', 'ad.announcement_id')
        ->where(function ($q) {
            $q->whereNull('a.id')->orWhere('a.is_active', 0);
        })
        ->select('ad.user_id', 'ad.announcement_id')
        ->get();
    foreach ($dismissalsToRemove as $row) {
        DB::table('announcement_dismissals')
            ->where('user_id', $row->user_id)
            ->where('announcement_id', $row->announcement_id)
            ->delete();
    }

    $zombieDeleted = DB::table('topics')
        ->whereNotIn('id', DB::table('posts')->distinct()->pluck('topic_id'))
        ->delete();
    $reports[] = "3. Zombi (Kayıp mesajli) konular ({$zombieDeleted} adet) silindi.";

    $overEdits = DB::table('post_edits')
        ->select('post_id')
        ->groupBy('post_id')
        ->havingRaw('COUNT(*) > 3')
        ->pluck('post_id');
    $deletedEdits = 0;
    foreach ($overEdits as $pId) {
        $keepIds = DB::table('post_edits')->where('post_id', $pId)->orderByDesc('id')->limit(3)->pluck('id')->all();
        if (!empty($keepIds)) {
            $deletedEdits += DB::table('post_edits')->where('post_id', $pId)->whereNotIn('id', $keepIds)->delete();
        }
    }
    $reports[] = "4. Mesaj Düzenleme Gecmis Listesi optimizasyonu: En yeni 3 kayit disindaki ({$deletedEdits} adet) eski versiyon gecmisi silindi.";

    $zombieEnabled = (string) (DB::table('settings')->where('key', 'zombie_control_enabled')->value('value') ?? '0') === '1';
    $zombieMonths = (int) (DB::table('settings')->where('key', 'zombie_inactive_months')->value('value') ?? 6);
    if ($zombieEnabled && $zombieMonths > 0) {
        $cutoff = now()->subMonths($zombieMonths)->format('Y-m-d H:i:s');
        $zombieUpdated = DB::table('users')
            ->where('is_suspended', 0)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $cutoff);
            })
            ->whereNotIn('role_id', [1])
            ->update(['is_suspended' => 1, 'suspended_at' => now()]);
        $reports[] = "5. Zombi (son {$zombieMonths} aydır giris yapmamis) kullanicilar: {$zombieUpdated} adet askiya alindi.";
    } else {
        $reports[] = "5. Zombi kontrolu kapali veya ayar 0.";
    }

    if (class_exists(\Plugins\Commerce\Commands\CalculateTrustScores::class)) {
        try {
            (new \Plugins\Commerce\Commands\CalculateTrustScores())->handle();
            $reports[] = "6. Commerce guven skorlari guncellendi.";
        } catch (\Throwable $e) {
            $reports[] = "6. Commerce guven skoru: HATA - " . $e->getMessage();
        }
    } else {
        $reports[] = "6. Commerce guven skoru atlandi (eklenti yok).";
    }

    try {
        $app->cache()->delete('sitemap_xml');
        $reports[] = "7. Sitemap onbellek temizlendi (site haritasi guncel tutulur).";
    } catch (\Throwable $e) {
        $reports[] = "7. Sitemap onbellek: " . $e->getMessage();
    }

    try {
        $versionCheckUrl = core_config('app.version_check_url', \App\Version::DEFAULT_VERSION_CHECK_URL);
        $versionSvc = new \App\Services\VersionCheckService($versionCheckUrl);
        $versionResult = $versionSvc->runCheck();
        if ($versionResult['success']) {
            $reports[] = "8. Surum kontrolu: " . $versionResult['message'];
        } else {
            $reports[] = "8. Surum kontrolu: " . ($versionResult['error'] ?? 'Bilinmeyen hata');
        }
    } catch (\Throwable $e) {
        $reports[] = "8. Surum kontrolu: " . $e->getMessage();
    }

    try {
        $rtbhOn = (string) (DB::table('settings')->where('key', 'rtbh_enabled')->value('value') ?? '0') === '1';
        if ($rtbhOn && class_exists(\App\Services\RtbhIpListService::class)) {
            $rtbhSvc = new \App\Services\RtbhIpListService($app);
            $rr = $rtbhSvc->refreshFromRemote();
            if (!empty($rr['success'])) {
                $reports[] = '9. RTBH IP listesi guncellendi (' . (int) ($rr['count'] ?? 0) . ' IP).';
            } else {
                $code = (string) ($rr['error'] ?? 'unknown');
                $rtbhSvc->recordRefreshError($code);
                $reports[] = '9. RTBH IP listesi: HATA - ' . $code;
            }
        } else {
            $reports[] = '9. RTBH: kapali veya servis yok (aciksa manuel veya cron ile guncellenir).';
        }
    } catch (\Throwable $e) {
        $reports[] = '9. RTBH: ' . $e->getMessage();
    }

    // ── 10. Uzak Takip Sunucusuna Ping ──
    try {
        $trackerFile = $basePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'RemoteTrackerService.php';
        if (is_file($trackerFile)) {
            require_once $trackerFile;
            $trackerResult = \App\Services\RemoteTrackerService::ping();
            $reports[] = '10. Uzak Takip: ' . $trackerResult['message'];
        } else {
            $reports[] = '10. Uzak Takip: Servis dosyası bulunamadı.';
        }
    } catch (\Throwable $e) {
        $reports[] = '10. Uzak Takip: HATA - ' . $e->getMessage();
    }

} catch (\Throwable $e) {
    http_response_code(500);
    die("CRON ERROR: " . $e->getMessage());
}

header('Content-Type: text/plain; charset=utf-8');
echo "CRON BASARIYLA TAMAMLANDI - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("-", 40) . "\n";
foreach ($reports as $r) {
    echo $r . "\n";
}
echo str_repeat("-", 40) . "\n";
