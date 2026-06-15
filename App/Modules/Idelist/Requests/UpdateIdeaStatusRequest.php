<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Requests;

use App\Http\Requests\FormRequest;
use App\Modules\Idelist\Models\IdeaStatusDefinition;

class UpdateIdeaStatusRequest extends FormRequest
{
    private bool $selectedStatusRequiresCompletion = false;

    public function rules(): array
    {
        $slugs = IdeaStatusDefinition::allSlugs();
        $inRule = $slugs !== [] ? 'in:' . implode(',', $slugs) : 'in:__none__';
        $selectedSlug = trim((string) ($this->data['status'] ?? ''));
        $selectedDef = $selectedSlug !== '' ? IdeaStatusDefinition::query()->where('slug', $selectedSlug)->first() : null;
        $this->selectedStatusRequiresCompletion = $selectedDef !== null && (bool) $selectedDef->requires_completion;

        return [
            'status' => 'required|' . $inRule,
            'completion_note' => ($this->selectedStatusRequiresCompletion ? 'required' : 'nullable') . '|max:5000',
            'completion_url' => 'nullable|max:500|url',
        ];
    }

    public function validate(): bool
    {
        if (isset($this->data['completion_url'])) {
            $this->data['completion_url'] = trim((string) $this->data['completion_url']);
        }
        if (isset($this->data['completion_note'])) {
            $this->data['completion_note'] = trim((string) $this->data['completion_note']);
        }

        return parent::validate();
    }

    public function firstError(): ?string
    {
        $error = parent::firstError();
        if ($error === 'completion_note required' || $error === 'completion_note is required') {
            return lang('idelist.completion_note_required');
        }

        return $error;
    }

    public function selectedStatusRequiresCompletion(): bool
    {
        return $this->selectedStatusRequiresCompletion;
    }
}
