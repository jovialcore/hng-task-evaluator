<?php

declare(strict_types=1);

namespace App\Service\HngX2;

class Evaluator
{
    public function rules(string $url): array
    {
        return [

            'name' => ['required', 'string'],
        ];
    }
}
