<?php

declare(strict_types=1);

namespace App\Service\Contracts;

use GuzzleHttp\Psr7\Response;

interface Evaluator
{
    public function rules(string $url): array;

    public function data(Response $response, string $url): array;

    public function fetch(array $urls): array;

    public function messages(): array;

    public function getContent(Response $response, string $url): array;

    public function getEvaluationData(string $url): array;

    public function csvFilePath(): string;

    public function csvHeaderColumns(): array;

    public function csvLine(array $item): array;
}
