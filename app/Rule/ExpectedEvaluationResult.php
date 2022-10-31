<?php

declare(strict_types=1);

namespace App\Rule;

use InvalidArgumentException;
use App\Service\GuessOperationType;
use Illuminate\Contracts\Validation\Rule;

final class ExpectedEvaluationResult implements Rule
{
    public const RESULT = 'result';
    public const OPERATION_TYPE = 'operation_type';

    protected function __construct(
        protected readonly mixed $expectedResult,
        protected readonly string $type,
    ) {
        match ($this->type) {
            self::RESULT, self::OPERATION_TYPE => null,
            default => throw new InvalidArgumentException('Invalid ExpectedOpResult type'),
        };
    }

    public static function is(mixed $result, ?string $type = null): self
    {
        return new self($result, $type ?? self::RESULT);
    }

    public function passes($attribute, $value): bool
    {
        return match ($this->type) {
            self::RESULT => $value == $this->expectedResult,
            self::OPERATION_TYPE => $value === GuessOperationType::of($this->expectedResult),
        };
    }

    public function message(): string
    {
        return 'Your :attribute is not the expected :attribute.';
    }
}
