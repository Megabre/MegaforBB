<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LanguageLine;

class Translator
{
    protected string $locale = 'tr';
    protected string $fallbackLocale = 'tr';
    protected array $lines = [];
    protected array $loadedLocales = [];
    protected string $basePath;
    /** @var \Forecor\Core\Application|null */
    protected $app = null;

    public function __construct(string $basePath, $app = null)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->app = $app;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        if (!isset($this->loadedLocales[$locale])) {
            $this->load($locale);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
        if (!isset($this->loadedLocales[$locale])) {
            $this->load($locale);
        }
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function load(string $locale): void
    {
        if (isset($this->loadedLocales[$locale])) {
            return;
        }

        $langDir = $this->basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang';
        $file = $langDir . DIRECTORY_SEPARATOR . $locale . '.php';
        if (!is_file($file)) {
            $file = $this->basePath . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.php';
        }
        $fileLines = is_file($file) ? (array) require $file : [];

        $dbLines = [];
        try {
            if (class_exists(LanguageLine::class)) {
                $dbLines = LanguageLine::getTranslationsForLocale($locale);
            }
        } catch (\Throwable $e) {
            // DB henüz hazır değilse sessizce devam et
        }

        $this->lines[$locale] = array_merge($fileLines, $dbLines);

        if ($this->app !== null && method_exists($this->app, 'hooks')) {
            $this->lines[$locale] = $this->app->hooks()->applyFilters('translator.lines', $this->lines[$locale], $locale);
        }

        $this->loadedLocales[$locale] = true;
    }

    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->loadedLocales[$locale])) {
            $this->load($locale);
        }

        $line = $this->lines[$locale][$key] ?? null;

        if ($line === null && $locale !== $this->fallbackLocale) {
            if (!isset($this->loadedLocales[$this->fallbackLocale])) {
                $this->load($this->fallbackLocale);
            }
            $line = $this->lines[$this->fallbackLocale][$key] ?? null;
        }

        $line = $line ?? $key;

        foreach ($replace as $k => $v) {
            $line = str_replace(':' . $k, (string) $v, $line);
        }

        return $line;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->loadedLocales[$locale])) {
            $this->load($locale);
        }

        return isset($this->lines[$locale][$key]);
    }

    /**
     * @return array<string, string>
     */
    public function all(?string $locale = null): array
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->loadedLocales[$locale])) {
            $this->load($locale);
        }

        return $this->lines[$locale] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function allForGroup(string $group, ?string $locale = null): array
    {
        $all = $this->all($locale);
        $prefix = $group . '.';
        $result = [];

        foreach ($all as $k => $v) {
            if (strpos($k, $prefix) === 0) {
                $result[$k] = $v;
            }
        }

        return $result;
    }
}
