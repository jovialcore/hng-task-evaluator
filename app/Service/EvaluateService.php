<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Contracts\Evaluator;
use Illuminate\Support\LazyCollection;

final class EvaluateService
{
    protected ?LazyCollection $failed = null;

    protected ?LazyCollection $passed = null;

    public function __construct(protected readonly ResponseValidator $validator)
    {
    }

    public function passedEvaluation(): LazyCollection
    {
        return $this->passed;
    }

    public function failedEvaluation(): LazyCollection
    {
        return $this->failed;
    }

    public function evaluate(array $urls, Evaluator $evaluator): void
    {
        $results = $evaluator->fetch($urls);

        $collection = LazyCollection::make(function () use ($results, $evaluator) {
            foreach ($results as $url => $result) {
                yield $this->parseAsyncResponses($result, $url, $evaluator);
            }
        });

        $this->passed = $collection->filter(fn ($item) => $item['passed']);
        $this->failed = $collection->filter(fn ($item) => ! $item['passed']);
    }

    private function parseAsyncResponses(array $result, string $url, Evaluator $evaluator): array
    {
        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = $result['value'] ?? null;

        if ($result['state'] !== 'fulfilled' || $response === null) {
            $errors = [$result['reason']?->getMessage() ?? 'An unknown error occurred'];

            return $this->buildPayload($url, $errors, false, []);
        }

        ['passed' => $passed, 'errors' => $errors] = $this->validator->validate(
            $evaluator->data($response),
            $evaluator->rules(),
            $evaluator->messages()
        );

        return $this->buildPayload($url, $errors, $passed, $evaluator->getContent($response));
    }

    private function buildPayload(string $url, array $errors = [], bool $passed = false, array $content = []): array
    {
        return ['url' => $url, 'content' => $content, 'passed' => $passed, 'errors' => $errors];
    }
}
