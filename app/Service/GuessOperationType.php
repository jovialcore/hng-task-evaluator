<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class GuessOperationType
{
    protected static array $aliasesOfAddition = ['addition', 'add', 'sum', 'plus', '+'];

    protected static array $aliasesOfSubtraction = ['subtraction', 'subtract', 'minus', 'difference', '-'];

    protected static array $aliasesOfMultiplication = ['multiplication', 'multiply', 'product', 'times', '*'];

    public static function of(string $typeString): string
    {
        if (self::containsAny($typeString, self::$aliasesOfAddition)) {
            return 'addition';
        }

        if (self::containsAny($typeString, self::$aliasesOfSubtraction)) {
            return 'subtraction';
        }

        if (self::containsAny($typeString, self::$aliasesOfMultiplication)) {
            return 'multiplication';
        }

        throw new RuntimeException('Unable to guess operation type');
    }

    private static function containsAny(string $typeString, array $aliasesOfAddition): bool
    {
        foreach ($aliasesOfAddition as $alias) {
            if (str_contains($typeString, $alias)) {
                return true;
            }
        }

        return false;
    }
}
