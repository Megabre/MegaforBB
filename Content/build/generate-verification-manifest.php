<?php

declare(strict_types=1);

use App\Services\FileVerificationService;
use App\Version;

$basePath = dirname(__DIR__);

require $basePath . DIRECTORY_SEPARATOR . 'Library' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$version = Version::VERSION;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--version=')) {
        $candidate = trim(substr($arg, 10));
        if ($candidate !== '') {
            $version = $candidate;
        }
    }
}

$service = new FileVerificationService($basePath);
$generated = $service->generateManifest($version);

if (!($generated['success'] ?? false)) {
    fwrite(STDERR, "Manifest olusturulamadi.\n");
    exit(1);
}

$manifestPath = (string) ($generated['path'] ?? '');
$fileCount = (int) ($generated['file_count'] ?? 0);

fwrite(STDOUT, "Manifest olusturuldu: {$manifestPath}\n");
fwrite(STDOUT, "Dosya sayisi: {$fileCount}\n");

