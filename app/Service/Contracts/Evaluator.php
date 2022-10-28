<?php

declare(strict_types=1);

namespace App\Service\Contracts;

use GuzzleHttp\Psr7\Response;

interface Evaluator
{
    public function rules(): array;

    public function data(Response $response): array;

    public function fetch(array $urls): array;

    public function messages(): array;

    public function getContent(Response $response): array;
}
