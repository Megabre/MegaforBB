<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportPolls implements ImportStepInterface
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
            SELECT p.poll_id, p.content_type, p.content_id, p.question,
                   p.max_votes, p.close_date, p.public_votes, p.change_vote
            FROM xf_poll p
            WHERE p.content_type = 'thread'
            ORDER BY p.poll_id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO polls
                (topic_id, question, max_votes, allow_change_vote, closes_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $topicCreatedAt = $targetPdo->prepare("SELECT created_at FROM topics WHERE id = ?");

        $existingPoll = $targetPdo->prepare('SELECT id FROM polls WHERE topic_id = ?');

        foreach ($rows as $row) {
            try {
                $pollId = (int) $row['poll_id'];
                $contentId = (int) $row['content_id'];

                $topicId = $mapper->get('topic', $contentId);
                if ($topicId === null) {
                    $result->skipped++;
                    continue;
                }

                $existingPoll->execute([$topicId]);
                $existingId = $existingPoll->fetchColumn();
                if ($existingId !== false) {
                    $mapper->add('poll', $pollId, (int) $existingId);
                    $result->skipped++;
                    continue;
                }

                $closeDate = (int) $row['close_date'];
                $closesAt = $closeDate > 0 ? date('Y-m-d H:i:s', $closeDate) : null;

                $topicCreatedAt->execute([$topicId]);
                $createdAt = $topicCreatedAt->fetchColumn() ?: date('Y-m-d H:i:s');

                $insert->execute([
                    $topicId,
                    $row['question'],
                    max(1, (int) $row['max_votes']),
                    (int) $row['change_vote'],
                    $closesAt,
                    $createdAt,
                ]);

                $newPollId = (int) $targetPdo->lastInsertId();
                $mapper->add('poll', $pollId, $newPollId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Poll #{$row['poll_id']}: {$e->getMessage()}";
            }
        }
    }

    private function importPollOptions(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("
            SELECT poll_response_id, poll_id, response, response_vote_count, voters
            FROM xf_poll_response
            ORDER BY poll_response_id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO poll_options (poll_id, option_text, vote_count, sort_order)
            VALUES (?, ?, ?, ?)
        ");
        $sortOrder = [];

        foreach ($rows as $row) {
            try {
                $responseId = (int) $row['poll_response_id'];
                $oldPollId = (int) $row['poll_id'];

                $newPollId = $mapper->get('poll', $oldPollId);
                if ($newPollId === null) {
                    $result->skipped++;
                    continue;
                }

                if (!isset($sortOrder[$newPollId])) {
                    $sortOrder[$newPollId] = 0;
                }
                $sortOrder[$newPollId]++;

                $insert->execute([
                    $newPollId,
                    $row['response'],
                    (int) $row['response_vote_count'],
                    $sortOrder[$newPollId],
                ]);

                $newOptionId = (int) $targetPdo->lastInsertId();
                $mapper->add('poll_option', $responseId, $newOptionId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "PollResponse #{$row['poll_response_id']}: {$e->getMessage()}";
            }
        }
    }

    private function importPollVotes(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("
            SELECT poll_response_id, user_id, vote_date
            FROM xf_poll_vote
            ORDER BY poll_response_id ASC, user_id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO poll_votes (poll_id, option_id, user_id, created_at)
            VALUES (?, ?, ?, ?)
        ");
        $lookupPoll = $targetPdo->prepare("SELECT poll_id FROM poll_options WHERE id = ?");

        foreach ($rows as $row) {
            try {
                $oldResponseId = (int) $row['poll_response_id'];
                $oldUserId = (int) $row['user_id'];

                $pollOptionId = $mapper->get('poll_option', $oldResponseId);
                if ($pollOptionId === null) {
                    $result->skipped++;
                    continue;
                }

                $userId = $mapper->get('user', $oldUserId);
                if ($userId === null) {
                    $result->skipped++;
                    continue;
                }

                $lookupPoll->execute([$pollOptionId]);
                $pollId = $lookupPoll->fetchColumn();
                if ($pollId === false) {
                    $result->skipped++;
                    continue;
                }

                $insert->execute([
                    (int) $pollId,
                    $pollOptionId,
                    $userId,
                    date('Y-m-d H:i:s', (int) $row['vote_date']),
                ]);

                $result->imported++;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $result->skipped++;
                    continue;
                }
                $result->errors++;
                $result->errorMessages[] = "PollVote response#{$row['poll_response_id']} user#{$row['user_id']}: {$e->getMessage()}";
            }
        }
    }
}
