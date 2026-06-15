<?php

declare(strict_types=1);

namespace App\Services;

use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Yüklenen görseller: EXIF temizleme, yön düzeltme, boyut sınırı (upsize yok), hafif keskinleştirme, WebP.
 * Intervention Image v3 ile; GD veya Imagick kullanılır.
 */
final class ImageProcessor
{
    private const WEBP_QUALITY = 80;
    /** Maksimum genişlik/yükseklik; sadece büyük görseller küçültülür, küçük görsel büyütülmez. */
    private const MAX_SIDE = 2048;
    private const SHARPEN_AMOUNT = 10;

    /**
     * Görseli işler (orient, scaleDown, sharpen, strip, WebP). Başarılı ise işlenmiş dosyanın yolu, hata ise null.
     * Dönen dosya geçici bir .webp dosyasıdır; çağıran kaydettikten sonra silebilir.
     */
    public static function processToWebp(string $tmpPath): ?string
    {
        if (!is_file($tmpPath) || !is_readable($tmpPath)) {
            return null;
        }
        try {
            $driver = extension_loaded('imagick') ? 'imagick' : 'gd';
            $manager = $driver === 'imagick'
                ? ImageManager::imagick()
                : ImageManager::gd();
            $image = $manager->read($tmpPath);
            $image->orient();
            // Sadece büyük görselleri küçült; küçük görseli asla büyütme (scaleDown)
            $image->scaleDown(self::MAX_SIDE, self::MAX_SIDE);
            $image->sharpen(self::SHARPEN_AMOUNT);
            $encoded = $image->encode(new WebpEncoder(quality: self::WEBP_QUALITY, strip: true));
            $outPath = $tmpPath . '.webp';
            $encoded->save($outPath);
            return $outPath;
        } catch (\Throwable $e) {
            error_log('ImageProcessor::processToWebp: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * MIME'ın işlenebilir görsel olup olmadığını söyler.
     */
    public static function isProcessableImage(string $mime): bool
    {
        return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
    }
}
