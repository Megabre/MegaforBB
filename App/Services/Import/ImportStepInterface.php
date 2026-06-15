<?php

declare(strict_types=1);

namespace App\Services\Import;

interface ImportStepInterface
{
    public function name(): string;

    public function key(): string;

    public function order(): int;

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult;
}
