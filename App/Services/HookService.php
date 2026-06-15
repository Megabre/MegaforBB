<?php

declare(strict_types=1);

namespace App\Services;

/**
 * WordPress tarzı hook API: Actions (HTML/enjeksiyon) ve Filters (veri dönüşümü).
 * Eklentiler ve tema bu kancalara bağlanır; çekirdek sadece hook noktalarını tetikler.
 *
 * - do_action($name, ...$args): Tüm kayıtlı callable'ları sırayla çalıştırır; dönüş değerleri birleştirilir (HTML için).
 * - apply_filters($name, $value, ...$args): Değeri zincirleme callable'lardan geçirir; son değer döner.
 */
class HookService
{
    /** @var array<string, array<int, array{priority: int, callable: callable}>> */
    private array $actions = [];

    /** @var array<string, array<int, array{priority: int, callable: callable}>> */
    private array $filters = [];

    private const DEFAULT_PRIORITY = 10;

    /**
     * Action kancasına callable ekler. Aynı isimle birden fazla eklenebilir; priority ile sıra belirlenir.
     */
    public function addAction(string $name, callable $callable, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->actions[$name] = $this->actions[$name] ?? [];
        $this->actions[$name][] = ['priority' => $priority, 'callable' => $callable];
        usort($this->actions[$name], fn ($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Filter kancasına callable ekler. Callable($value, ...$args) döndürmeli.
     */
    public function addFilter(string $name, callable $callable, int $priority = self::DEFAULT_PRIORITY): void
    {
        $this->filters[$name] = $this->filters[$name] ?? [];
        $this->filters[$name][] = ['priority' => $priority, 'callable' => $callable];
        usort($this->filters[$name], fn ($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Action tetikler; tüm callable'ların dönüş değerleri (string) birleştirilir.
     * Layout/tema enjeksiyonu için: her callable HTML döndürebilir.
     */
    public function doAction(string $name, ...$args): string
    {
        $out = '';
        if (!isset($this->actions[$name])) {
            return $out;
        }
        foreach ($this->actions[$name] as $item) {
            try {
                $result = $item['callable'](...$args);
                if (is_string($result)) {
                    $out .= $result;
                }
            } catch (\Throwable $e) {
                // Bir eklenti hata verirse diğerleri çalışsın
            }
        }
        return $out;
    }

    /**
     * Filter tetikler; value sırayla her callable'dan geçer: value = callable(value, ...$args).
     */
    public function applyFilters(string $name, $value, ...$args)
    {
        if (!isset($this->filters[$name])) {
            return $value;
        }
        foreach ($this->filters[$name] as $item) {
            try {
                $value = $item['callable']($value, ...$args);
            } catch (\Throwable $e) {
                // Hata veren filter atlanır
            }
        }
        return $value;
    }

    /** Belirli bir action'da kayıtlı callable var mı? */
    public function hasAction(string $name): bool
    {
        return !empty($this->actions[$name]);
    }

    /** Belirli bir filter'da kayıtlı callable var mı? */
    public function hasFilter(string $name): bool
    {
        return !empty($this->filters[$name]);
    }
}
