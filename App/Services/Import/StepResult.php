<?php

declare(strict_types=1);

namespace App\Services\Import;

class StepResult
{
    public int $total = 0;
    public int $imported = 0;
    public int $skipped = 0;
    public int $errors = 0;
    public array $errorMessages = [];

    public function isSuccess(): bool
    {
        return $this->errors === 0;
    }

    public function summary(): string
    {
        $parts = [
            "Total: {$this->total}",
            "Imported: {$this->imported}",
            "Skipped: {$this->skipped}",
            "Errors: {$this->errors}",
        ];

        $line = implode(' | ', $parts);

        if ($this->errorMessages) {
            $line .= "\n  " . implode("\n  ", array_slice($this->errorMessages, 0, 20));
            if (count($this->errorMessages) > 20) {
                $line .= "\n  ... and " . (count($this->errorMessages) - 20) . " more";
            }
        }

        return $line;
    }
}
