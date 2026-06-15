<?php

declare(strict_types=1);

namespace App\Core;

/**
 * ClassProxyManager
 * MegaforBB sistemine XenForo tarzı Sınıf Genişletme (Class Extension) yeteneği kazandırır.
 *
 * Eklentiler (Addons/Plugins) çekirdek dosyaya dokunmadan bu sınıf sayesinde
 * çekirdek sınıflarının arasına girerek metotları override edebilir.
 */
class ClassProxyManager
{
    /**
     * @var array<string, array<string>>
     * Key: Orijinal Sınıf (örn: App\Services\TopicService)
     * Value: Genişleten Sınıflar Dizisi (örn: ['MyPlugin\TopicService'])
     */
    protected static array $extensions = [];

    /**
     * @var array<string, string> Resolve edilmiş nihai sınıf isimleri önbelleği.
     */
    protected static array $resolved = [];

    /**
     * XFCP (Proxy) sınıfları için oto-yükleyici başlatılıp başlatılmadığı bayrağı.
     */
    protected static bool $autoloaderRegistered = false;

    /**
     * Sistemi XFCP_ ön ekli sanal sınıflar için dinlemeye başlar.
     */
    public static function registerAutoloader(): void
    {
        if (self::$autoloaderRegistered) {
            return;
        }

        spl_autoload_register(function ($class) {
            // Örn: MyPlugin\XFCP_TopicService
            if (str_contains($class, '\\XFCP_')) {
                self::resolveAliasTarget($class);
            }
        }, true, true); // Prepend true

        self::$autoloaderRegistered = true;
    }

    /**
     * Çağrılan `$proxyClass` (Örn: MyPlugin\XFCP_TopicService) için
     * zincirin bir önceki parçasını bularak class_alias oluşturur.
     */
    protected static function resolveAliasTarget(string $proxyClass): void
    {
        $proxyClass = ltrim($proxyClass, '\\');

        // Eklenti sınıfını türetelim (örn MyPlugin\XFCP_TopicService -> MyPlugin\TopicService varsayılır)
        $parts = explode('\\XFCP_', $proxyClass);
        if (count($parts) !== 2) {
            return; // XFCP yapısı değil
        }
        $pluginNamespace = $parts[0];
        $className = $parts[1];
        $expectedPluginClass = $pluginNamespace . '\\' . $className;

        // Tüm extension listesini tara
        foreach (self::$extensions as $originalClass => $extList) {
            $index = array_search($expectedPluginClass, $extList, true);
            if ($index !== false) {
                // Eğer bu listedeki İLK eklentiyse ata sınıfı asıl Orijinal Sınıftır.
                if ($index === 0) {
                    $parentTarget = $originalClass;
                } else {
                    // Eğer 2. eklentiyse, bir önceki eklenti asıl ata sınıfıdır (Target)
                    $parentTarget = $extList[$index - 1];
                }

                if (!class_exists($proxyClass, false)) {
                    class_alias($parentTarget, $proxyClass);
                }
                return;
            }
        }
    }

    /**
     * Bir sınıfı genişletmek için sisteme kaydeder. (Genellikle sistem başlatılırken çağrılır).
     *
     * @param string $originalClass Genişletilecek ana sınıf (örn: App\Services\TopicService)
     * @param string $extensionClass Genişleten eklenti sınıfı (örn: MyPlugin\XFCP_TopicService)
     */
    public static function extend(string $originalClass, string $extensionClass): void
    {
        $originalClass = ltrim($originalClass, '\\');
        $extensionClass = ltrim($extensionClass, '\\');

        if (!isset(self::$extensions[$originalClass])) {
            self::$extensions[$originalClass] = [];
        }

        if (!in_array($extensionClass, self::$extensions[$originalClass], true)) {
            self::$extensions[$originalClass][] = $extensionClass;
        }

        // Önbelleği temizle (yeni eklenti eklendiyse zincir yeniden hesaplansın)
        unset(self::$resolved[$originalClass]);
    }

    /**
     * Sınıfı başlatmak için çağrılır.
     * Klasik `new Class()` yerine `ClassProxyManager::resolve(Class::class)` kullanılmalıdır.
     *
     * @param string $class İstek yapılan orijinal sınıf
     * @return string Nihai nesneleştirilecek sınıf adı
     */
    public static function resolve(string $class): string
    {
        $class = ltrim($class, '\\');

        if (isset(self::$resolved[$class])) {
            return self::$resolved[$class];
        }

        self::registerAutoloader();

        if (empty(self::$extensions[$class])) {
            self::$resolved[$class] = $class;
            return $class;
        }

        // Zincirin EN SON halkası (en son eklenen eklenti) çözümlenecek nihai sınıftır
        $lastClass = end(self::$extensions[$class]);
        self::$resolved[$class] = $lastClass;

        return $lastClass;
    }

    /**
     * Yardımcı Factory Metodu
     * Dinamik olarak sınıfı çözümler ve yeni bir örneğini (instance) oluşturur.
     *
     * @param string $class Sınıf adı
     * @param mixed ...$args Constructor argümanları
     * @return mixed
     */
    public static function instantiate(string $class, ...$args)
    {
        $resolvedClass = self::resolve($class);
        return new $resolvedClass(...$args);
    }

    /**
     * Tüm uzantıları temizler (Testler için vb.)
     */
    public static function clear(): void
    {
        self::$extensions = [];
        self::$resolved = [];
    }
}
