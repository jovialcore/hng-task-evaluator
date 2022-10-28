<?php

declare(strict_types=1);

namespace App\Service\Contracts;

interface Evaluate
{
    public function evaluate(array $urls): void;

    /**
     * @return array<int, string>
     */
    public function passedEvaluation(): array;

    /**
     * @return array<int, string>
     */
    public function failedEvaluation(): array;
}
