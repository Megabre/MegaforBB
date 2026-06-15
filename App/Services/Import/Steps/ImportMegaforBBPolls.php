<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBPolls implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_polls');
    }

    public function key(): string
    {
        return 'polls';
    }

    public function order(): int
    {
        return 60;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $mapper->preload('topic');
        $mapper->preload('user');

        $this->importPolls($sourcePdo, $targetPdo, $mapper, $result);
        $this->importPollOptions($sourcePdo, $targetPdo, $mapper, $result);
        $this->importPollVotes($sourcePdo, $targetPdo, $mapper, $result);

        return $result;
    }

    private function importPolls(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("
            SELECT id, topic_id, question, max_votes, allow_change_vote, closes_at, created_at
            FROM polls
            ORDER BY id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO polls (topic_id, question, max_votes, allow_change_vote, closes_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $existingPoll = $targetPdo->prepare('SELECT id FROM polls WHERE topic_id = ? LIMIT 1');

        foreach ($rows as $row) {
            try {
                $oldPollId = (int) $row['id'];
                $topicId = $mapper->get('topic', (int) $row['topic_id']);
                if ($topicId === null) {
                    $result->skipped++;
                    continue;
                }

                $existingPoll->execute([$topicId]);
                $existingId = $existingPoll->fetchColumn();
                if ($existingId !== false) {
                    $mapper->add('poll', $oldPollId, (int) $existingId);
                    $result->skipped++;
                    continue;
                }

                $insert->execute([
                    $topicId,
                    $row['question'],
                    max(1, (int) ($row['max_votes'] ?? 1)),
                    (int) ($row['allow_change_vote'] ?? 0),
                    $row['closes_at'] ?? null,
                    $row['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $newPollId = (int) $targetPdo->lastInsertId();
                $mapper->add('poll', $oldPollId, $newPollId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Poll #{$row['id']}: {$e->getMessage()}";
            }
        }
    }

    private function importPollOptions(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("
            SELECT id, poll_id, option_text, vote_count, sort_order
            FROM poll_options
            ORDER BY poll_id ASC, sort_order ASC, id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO poll_options (poll_id, option_text, vote_count, sort_order)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldOptionId = (int) $row['id'];
                $newPollId = $mapper->get('poll', (int) $row['poll_id']);
                if ($newPollId === null) {
                    $result->skipped++;
                    continue;
                }

                $insert->execute([
                    $newPollId,
                    $row['option_text'],
                    (int) ($row['vote_count'] ?? 0),
                    (int) ($row['sort_order'] ?? 0),
                ]);
                $newOptionId = (int) $targetPdo->lastInsertId();
                $mapper->add('poll_option', $oldOptionId, $newOptionId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "PollOption #{$row['id']}: {$e->getMessage()}";
            }
        }
    }

    private function importPollVotes(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("
            SELECT id, poll_id, option_id, user_id, created_at
            FROM poll_votes
            ORDER BY id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO poll_votes (poll_id, option_id, user_id, created_at)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $newPollId = $mapper->get('poll', (int) $row['poll_id']);
                $newOptionId = $mapper->get('poll_option', (int) $row['option_id']);
                $userId = $mapper->get('user', (int) $row['user_id']);
                if ($newPollId === null || $newOptionId === null || $userId === null) {
                    $result->skipped++;
                    continue;
                }

                $insert->execute([
                    $newPollId,
                    $newOptionId,
                    $userId,
                    $row['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $result->skipped++;
                    continue;
                }
                $result->errors++;
                $result->errorMessages[] = "PollVote #{$row['id']}: {$e->getMessage()}";
            }
        }
    }
}
