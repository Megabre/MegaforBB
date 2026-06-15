<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Services\Import\Steps\ImportMegaforBBForums;
use App\Services\Import\Steps\ImportMegaforBBPolls;
use App\Services\Import\Steps\ImportMegaforBBPosts;
use App\Services\Import\Steps\ImportMegaforBBPrefixes;
use App\Services\Import\Steps\ImportMegaforBBRoles;
use App\Services\Import\Steps\ImportMegaforBBSocial;
use App\Services\Import\Steps\ImportMegaforBBTopics;
use App\Services\Import\Steps\ImportMegaforBBUsers;
use App\Services\Import\Steps\SyncCounters;

class MegaforBBImporter
{
    private \PDO $sourcePdo;
    private \PDO $targetPdo;
    private IdMapper $mapper;
    private string $source = 'megaforbb';

    /**
     * @param \PDO       $targetPdo MegaforBB database (yazilacak veritabani)
     * @param \PDO|null  $sourcePdo Kaynak MegaforBB database (okunacak); null ise targetPdo kullanilir
     */
    public function __construct(\PDO $targetPdo, ?\PDO $sourcePdo = null, string $source = 'megaforbb')
    {
        $this->targetPdo = $targetPdo;
        $this->sourcePdo = $sourcePdo ?? $targetPdo;
        $this->source = $source;
        $this->mapper = new IdMapper($targetPdo, $source);
    }

    /** @return ImportStepInterface[] */
    public function getSteps(): array
    {
        $steps = [
            new ImportMegaforBBRoles(),
            new ImportMegaforBBUsers(),
            new ImportMegaforBBForums(),
            new ImportMegaforBBPrefixes(),
            new ImportMegaforBBTopics(),
            new ImportMegaforBBPosts(),
            new ImportMegaforBBPolls(),
            new ImportMegaforBBSocial(),
            new SyncCounters(),
        ];

        usort($steps, fn (ImportStepInterface $a, ImportStepInterface $b) => $a->order() <=> $b->order());

        return $steps;
    }

    public function runStep(string $stepKey, array $options = []): StepResult
    {
        $step = null;
        foreach ($this->getSteps() as $s) {
            if ($s->key() === $stepKey) {
                $step = $s;
                break;
            }
        }

        if ($step === null) {
            $result = new StepResult();
            $result->errors = 1;
            $result->errorMessages[] = "Unknown step: {$stepKey}";
            return $result;
        }

        $this->upsertProgress($stepKey, 'running');

        try {
            $result = $step->run($this->sourcePdo, $this->targetPdo, $this->mapper, $options);

            $status = ($result->imported > 0 || $result->errors === 0) ? 'completed' : 'failed';
            $this->updateProgress($stepKey, $status, $result->total, $result->imported, $result->errors);

            if ($result->errorMessages) {
                $this->logErrors($stepKey, $result->errorMessages);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->updateProgress($stepKey, 'failed', 0, 0, 1);
            $this->logErrors($stepKey, [$e->getMessage()]);

            $result = new StepResult();
            $result->errors = 1;
            $result->errorMessages[] = $e->getMessage();
            return $result;
        }
    }

    public function getProgress(): array
    {
        $st = $this->targetPdo->prepare("
            SELECT step, status, total_rows, processed_rows, error_count,
                   started_at, completed_at
            FROM import_progress
            WHERE source = ?
            ORDER BY id ASC
        ");
        $st->execute([$this->source]);

        $progress = [];
        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $progress[$row['step']] = [
                'status'    => $row['status'],
                'total'     => (int) $row['total_rows'],
                'processed' => (int) $row['processed_rows'],
                'errors'    => (int) $row['error_count'],
                'started'   => $row['started_at'],
                'completed' => $row['completed_at'],
            ];
        }

        return $progress;
    }

    public function resetProgress(): void
    {
        $this->targetPdo->prepare("DELETE FROM import_progress WHERE source = ?")->execute([$this->source]);
        $this->targetPdo->prepare("DELETE FROM import_errors WHERE source = ?")->execute([$this->source]);
        $this->mapper->clear();
    }

    public function getMapper(): IdMapper
    {
        return $this->mapper;
    }

    private function upsertProgress(string $stepKey, string $status): void
    {
        $st = $this->targetPdo->prepare("
            INSERT INTO import_progress (source, step, status, started_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE status = VALUES(status), started_at = NOW(),
                                    completed_at = NULL, error_count = 0,
                                    processed_rows = 0
        ");
        $st->execute([$this->source, $stepKey, $status]);
    }

    private function updateProgress(string $stepKey, string $status, int $total, int $processed, int $errors): void
    {
        $completedAt = ($status === 'completed' || $status === 'failed') ? date('Y-m-d H:i:s') : null;

        $st = $this->targetPdo->prepare("
            UPDATE import_progress
            SET status = ?, total_rows = ?, processed_rows = ?, error_count = ?,
                completed_at = ?
            WHERE source = ? AND step = ?
        ");
        $st->execute([$status, $total, $processed, $errors, $completedAt, $this->source, $stepKey]);
    }

    private function logErrors(string $stepKey, array $messages): void
    {
        $st = $this->targetPdo->prepare("
            INSERT INTO import_errors (source, step, error_message)
            VALUES (?, ?, ?)
        ");

        foreach (array_slice($messages, 0, 500) as $msg) {
            $st->execute([$this->source, $stepKey, $msg]);
        }
    }
}
