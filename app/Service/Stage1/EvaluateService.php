<?php

declare(strict_types=1);

namespace App\Service\Stage1;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Service\Contracts\Evaluate;

final class EvaluateService implements Evaluate
{
    /** @var array<int, string> */
    protected array $passed = [];

    /** @var array<int, string> */
    protected array $failed = [];

    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * @throws \Throwable
     */
    public function evaluate(array $urls): void
    {
        $promises = collect($urls)->map(fn (string $url) => $this->client->getAsync($url))->toArray();

        $results = Promise\Utils::settle($promises)->wait();

        foreach ($results as $url => $result) {
            $this->handleResponses($result, $url);
        }
    }

    public function passedEvaluation(): array
    {
        return $this->passed;
    }

    public function failedEvaluation(): array
    {
        return $this->failed;
    }

    private function handleResponses(array $result, int|string $url): void
    {
        if ($result['state'] === 'fulfilled') {
            $this->passed[] = [
                'url' => $url,
                'passed' => true,
                'reasons' => [],
            ];
        } else {
            $this->failed[] = [
                'url' => $url,
                'passed' => false,
                'reasons' => [$result['reason']->getMessage()],
            ];
        }
    }
}
