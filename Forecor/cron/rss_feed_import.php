<?php

declare(strict_types=1);

/**
 * Zamanı gelen RSS kaynaklarını çeker; yeni öğeler için hedef forumda konu açar.
 * public/cron.php içinden yüklenir; $app ve $reports tanımlı olmalıdır.
 */

use App\Services\Rss\RssFeedImportRunner;

if (!isset($app) || !($app instanceof \Forecor\Core\Application)) {
    return;
}

$runner = new RssFeedImportRunner($app);
$result = $runner->runDueFeeds();
$reports[] = 'RSS içe aktarma: ' . (int) $result['processed'] . ' kaynak işlendi, ' . (int) $result['imported'] . ' yeni konu oluşturuldu.';
if (!empty($result['errors'])) {
    $reports[] = 'RSS uyarıları: ' . implode(' | ', array_slice($result['errors'], 0, 8));
}
