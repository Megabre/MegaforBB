<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMyBBPolls implements ImportStepInterface
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
            SELECT pid, tid, question, dateline, timeout, closed, multiple, maxoptions
            FROM mybb_polls
            ORDER BY pid ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO polls
                (topic_id, question, max_votes, allow_change_vote, closes_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $topicCreatedAt = $targetPdo->prepare('SELECT created_at FROM topics WHERE id = ?');
        $existingPoll = $targetPdo->prepare('SELECT id FROM polls WHERE topic_id = ?');

        foreach ($rows as $row) {
            try {
                $pollId = (int) $row['pid'];
                $tid = (int) $row['tid'];
                $topicId = $mapper->get('topic', $tid);
                if ($topicId === null) {
                    $result->skipped++;
                    continue;
                }

                $existingPoll->execute([$topicId]);
                if ($existingPoll->fetchColumn() !== false) {
                    $result->skipped++;
                    continue;
                }

                $timeout = (int) $row['timeout'];
                $closesAt = $timeout > 0 ? date('Y-m-d H:i:s', $timeout) : null;
                $topicCreatedAt->execute([$topicId]);
                $createdAt = $topicCreatedAt->fetchColumn() ?: date('Y-m-d H:i:s');
                $maxVotes = max(1, (int) $row['maxoptions']);

                $insert->execute([
                    $topicId,
                    $row['question'],
                    $maxVotes,
                    0,
                    $closesAt,
                    $createdAt,
                ]);
                $newPollId = (int) $targetPdo->lastInsertId();
                $mapper->add('poll', $pollId, $newPollId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Poll #{$row['pid']}: {$e->getMessage()}";
            }
        }
    }

    /**
     * MyBB stores options as serialized array (e.g. 1=>"Option A", 2=>"Option B") or similar
     */
    private function parseOptionsString(string $optionsText): array
    {
        $arr = @unserialize($optionsText);
        if (is_array($arr)) {
            $out = [];
            foreach ($arr as $key => $val) {
                $out[(int) $key] = is_string($val) ? $val : (string) $val;
            }
            return $out;
        }
        $out = [];
        $lines = preg_split('/\r?\n/', trim($optionsText));
        foreach ($lines as $i => $line) {
            $idx = $i + 1;
            if (strpos($line, '||') !== false) {
                $parts = explode('||', $line, 2);
                $idx = (int) $parts[0];
                $out[$idx] = trim($parts[1] ?? '');
            } else {
                $out[$idx] = trim($line);
            }
        }
        return $out;
    }

    private function importPollOptions(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("SELECT pid, options, votes FROM mybb_polls ORDER BY pid ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO poll_options (poll_id, option_text, vote_count, sort_order) VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldPollId = (int) $row['pid'];
                $newPollId = $mapper->get('poll', $oldPollId);
                if ($newPollId === null) {
                    $result->skipped++;
                    continue;
                }
                $optionsMap = $this->parseOptionsString($row['options']);
                if (empty($optionsMap)) {
                    continue;
                }
                $voteCounts = [];
                if (!empty($row['votes'])) {
                    $voteCounts = @unserialize($row['votes']);
                    if (!is_array($voteCounts)) {
                        $voteCounts = [];
                    }
                }
                $sortOrder = 0;
                foreach ($optionsMap as $optKey => $optText) {
                    $sortOrder++;
                    $voteCount = isset($voteCounts[$optKey]) ? (int) $voteCounts[$optKey] : 0;
                    $insert->execute([$newPollId, $optText, $voteCount, $sortOrder]);
                    $newOptionId = (int) $targetPdo->lastInsertId();
                    $mapper->add('poll_option_' . $oldPollId, $optKey, $newOptionId);
                }
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Poll options #{$row['pid']}: {$e->getMessage()}";
            }
        }
    }

    private function importPollVotes(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        $rows = $sourcePdo->query("
            SELECT vid, pid, uid, voteoption, dateline
            FROM mybb_pollvotes
            ORDER BY vid ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $insert = $targetPdo->prepare("
            INSERT INTO poll_votes (poll_id, option_id, user_id, created_at) VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldPollId = (int) $row['pid'];
                $voteOption = (int) $row['voteoption'];
                $newPollId = $mapper->get('poll', $oldPollId);
                if ($newPollId === null) {
                    $result->skipped++;
                    continue;
                }
                $newOptionId = $mapper->get('poll_option_' . $oldPollId, $voteOption);
                if ($newOptionId === null) {
                    $result->skipped++;
                    continue;
                }
                $userId = $mapper->get('user', (int) $row['uid']);
                if ($userId === null) {
                    $result->skipped++;
                    continue;
                }
                $insert->execute([
                    $newPollId,
                    $newOptionId,
                    $userId,
                    date('Y-m-d H:i:s', (int) $row['dateline']),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $result->skipped++;
                    continue;
                }
                $result->errors++;
                $result->errorMessages[] = "Poll vote #{$row['vid']}: {$e->getMessage()}";
            }
        }
    }
}
