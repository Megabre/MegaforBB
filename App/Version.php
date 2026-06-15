<?php

declare(strict_types=1);

namespace App;

/**
 * Uygulama sürümü — tek kaynak (admin panelden değiştirilmez).
 * Sürüm yalnızca dağıtım paketinde bu dosya güncellenerek değişir.
 * Dosya doğrulama (file verification) bu sürüme göre manifest üretir.
 *
 * @version 1.1.3
 */
final class Version
{
    public const VERSION = '1.1.3';

    /** GitHub vb. üzerinden günlük kontrol için varsayılan URL (config ile override edilebilir). */
    public const DEFAULT_VERSION_CHECK_URL = 'https://raw.githubusercontent.com/Megabre/MegaforBB/refs/heads/main/version.json';
}
