<?php

declare(strict_types=1);

namespace App\Service\Stage2;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;

final class TestDataSample implements Arrayable
{
    protected array $normal = [
        ['operation_type' => 'addition', 'x' => 1, 'y' => 2, 'result' => 3],
        ['operation_type' => 'subtraction', 'x' => 10, 'y' => 2, 'result' => 8],
        ['operation_type' => 'multiplication', 'x' => 30, 'y' => 2, 'result' => 60],
        ['operation_type' => 'addition', 'x' => 20000000, 'y' => 20000000, 'result' => 40000000],
        ['operation_type' => 'subtraction', 'x' => 20000, 'y' => 20000000, 'result' => -19980000],
        ['operation_type' => 'multiplication', 'x' => 20000000, 'y' => 20000000, 'result' => 400000000000000],
    ];

    protected array $bonus = [
        ['operation_type' => 'What is the product of', 'x' => 5, 'y' => 5, 'result' => 25],
        ['operation_type' => 'What is the sum of', 'x' => 5, 'y' => 5, 'result' => 10],
        ['operation_type' => 'What is the difference of', 'x' => 5, 'y' => 20, 'result' => -15],
        ['operation_type' => 'What is the product of', 'x' => 50, 'y' => 266, 'result' => 13300],
        ['operation_type' => 'Can you add', 'x' => 182, 'y' => 1777, 'result' => 1959],
        ['operation_type' => 'Hey, please subtract', 'x' => 5, 'y' => 20, 'result' => -15],
        ['operation_type' => 'Can find the result of x*y', 'x' => 50, 'y' => 266, 'result' => 13300],
        ['operation_type' => 'What is x+y', 'x' => 182, 'y' => 1777, 'result' => 1959],
        ['operation_type' => 'What is x-y', 'x' => 5, 'y' => 20, 'result' => -15],
        ['operation_type' => 'What is the product of', 'x' => 50, 'y' => 266, 'result' => 13300],
        ['operation_type' => 'Can you add', 'x' => 182, 'y' => 1777, 'result' => 1959],
        ['operation_type' => 'What is the difference of', 'x' => 5, 'y' => 20, 'result' => -15],
        ['operation_type' => 'What is the product of', 'x' => 50, 'y' => 266, 'result' => 13300],
        ['operation_type' => 'Can you add', 'x' => 182, 'y' => 1777, 'result' => 1959],
        ['operation_type' => 'Whats the result when you subtract', 'x' => 2738, 'y' => 111, 'result' => 2627],
    ];

    protected array $data = [];

    public function __construct(bool $bonus = false)
    {
        $this->data = $bonus ? $this->bonus : $this->normal;

        if (APP_DEBUG) {
            $this->data = $bonus ? [$this->bonus[0]] : [$this->normal[0]];
        }
    }

    public function random(): array
    {
        return Arr::random($this->toArray());
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
