<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Services;

use App\Events\IdeaStatusChanged;
use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Models\IdeaStatusDefinition;
use InvalidArgumentException;

class IdeaStatusService
{
    public function transition(Idea $idea, string $newStatus, array $extra = []): void
    {
        $oldStatus = (string) $idea->status;
        $newDef = IdeaStatusDefinition::query()->where('slug', $newStatus)->first();
        if ($newDef === null) {
            throw new InvalidArgumentException('Invalid status value.');
        }
        $oldDef = IdeaStatusDefinition::query()->where('slug', $oldStatus)->first();

        if ($oldStatus === $newStatus) {
            $completionNote = trim((string) ($extra['completion_note'] ?? ''));
            $completionUrl = trim((string) ($extra['completion_url'] ?? ''));
            if ($newDef->requires_completion) {
                $idea->completion_note = $completionNote !== '' ? $completionNote : $idea->completion_note;
                $idea->completion_url = $completionUrl !== '' ? $completionUrl : $idea->completion_url;
                $idea->save();
            }

            return;
        }

        $completionNote = trim((string) ($extra['completion_note'] ?? ''));
        $completionUrl = trim((string) ($extra['completion_url'] ?? ''));

        if ($newDef->requires_completion) {
            if ($completionNote === '') {
                throw new InvalidArgumentException('Completion note required.');
            }
            if ($completionUrl !== '' && !filter_var($completionUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Completion URL invalid.');
            }
            $idea->completion_note = $completionNote;
            $idea->completion_url = $completionUrl !== '' ? $completionUrl : null;
        } elseif ($oldDef === null || !$oldDef->requires_completion) {
            $idea->completion_note = null;
            $idea->completion_url = null;
        }

        $idea->status = $newStatus;
        $idea->save();

        app()?->event()->dispatch(new IdeaStatusChanged($idea, $oldStatus, $newStatus), IdeaStatusChanged::NAME);
    }
}
