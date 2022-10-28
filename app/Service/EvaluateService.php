<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Contracts\Evaluator;

final class EvaluateService
{
    protected array $failed = [];

    protected array $passed = [];

    public function __construct(protected readonly ResponseValidator $validator)
    {
    }

    public function passedEvaluation(): array
    {
        return $this->passed;
    }

    public function failedEvaluation(): array
    {
        return $this->failed;
    }

    public function evaluate(array $urls, Evaluator $evaluator): void
    {
        $results = $evaluator->fetch($urls);

        foreach ($results as $url => $result) {
            $this->parseAsyncResponses($result, $url, $evaluator);
        }
    }

    private function parseAsyncResponses(array $result, string $url, Evaluator $evaluator): void
    {
        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $result['value'] ?? null;

        if ($result['state'] !== 'fulfilled' || $response === null) {
            $errors = [$result['reason']?->getMessage() ?? 'An unknown error occurred'];
            $this->failed[] = $this->buildPayload($url, $errors, false, []);

            return;
        }

        ['passed' => $passed, 'errors' => $errors] = $this->validator->validate(
            $evaluator->data($response),
            $evaluator->rules(),
            $evaluator->messages()
        );

        $key = $passed ? 'passed' : 'failed';
        $this->{$key}[] = $this->buildPayload($url, $errors, $passed, $evaluator->getContent($response));
    }

    private function buildPayload(string $url, array $errors = [], bool $passed = false, array $content = []): array
    {
        return ['url' => $url, 'content' => $content, 'passed' => $passed, 'errors' => $errors];
    }
}
