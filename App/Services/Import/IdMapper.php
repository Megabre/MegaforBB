<?php

declare(strict_types=1);

namespace App\Services\Import;

class IdMapper
{
    private \PDO $pdo;
    private string $source;
    private array $cache = [];

    public function __construct(\PDO $pdo, string $source = 'xenforo')
    {
        $this->pdo = $pdo;
        $this->source = $source;
    }

    public function add(string $entityType, int $oldId, int $newId, ?array $extra = null): void
    {
        $st = $this->pdo->prepare("
            INSERT INTO import_id_map (source, entity_type, old_id, new_id, extra)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE new_id = VALUES(new_id), extra = VALUES(extra)
        ");
        $st->execute([
            $this->source,
            $entityType,
            $oldId,
            $newId,
            $extra !== null ? json_encode($extra) : null,
        ]);

        $this->cache[$entityType][$oldId] = $newId;
    }

    public function get(string $entityType, int $oldId): ?int
    {
        if (isset($this->cache[$entityType][$oldId])) {
            return $this->cache[$entityType][$oldId];
        }

        $st = $this->pdo->prepare("
            SELECT new_id FROM import_id_map
            WHERE source = ? AND entity_type = ? AND old_id = ?
            LIMIT 1
        ");
        $st->execute([$this->source, $entityType, $oldId]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $newId = (int) $row['new_id'];
        $this->cache[$entityType][$oldId] = $newId;

        return $newId;
    }

    public function preload(string $entityType): void
    {
        $st = $this->pdo->prepare("
            SELECT old_id, new_id FROM import_id_map
            WHERE source = ? AND entity_type = ?
        ");
        $st->execute([$this->source, $entityType]);

        if (!isset($this->cache[$entityType])) {
            $this->cache[$entityType] = [];
        }

        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $this->cache[$entityType][(int) $row['old_id']] = (int) $row['new_id'];
        }
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function clear(?string $entityType = null): void
    {
        if ($entityType !== null) {
            $st = $this->pdo->prepare("
                DELETE FROM import_id_map WHERE source = ? AND entity_type = ?
            ");
            $st->execute([$this->source, $entityType]);
            unset($this->cache[$entityType]);
        } else {
            $st = $this->pdo->prepare("DELETE FROM import_id_map WHERE source = ?");
            $st->execute([$this->source]);
            $this->cache = [];
        }
    }
}
