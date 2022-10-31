<?php

declare(strict_types=1);

namespace App\Service;

use League\Csv\Reader;
use League\Csv\Writer;
use App\Service\Contracts\Evaluator;
use Illuminate\Support\LazyCollection;

final class EvaluateService
{
    protected ?LazyCollection $failed = null;
    protected ?LazyCollection $passed = null;

    public function __construct(protected readonly ResponseValidator $validator)
    {
        $this->failed = LazyCollection::make();
        $this->passed = LazyCollection::make();
    }

    public function passedEvaluation(): LazyCollection
    {
        return $this->passed;
    }

    public function failedEvaluation(): LazyCollection
    {
        return $this->failed;
    }

    public function failedErrors(): array
    {
        return $this->failed->map(fn (array $item) => $item['errors'])->flatten()->toArray();
    }

    public function allSuccessful(): bool
    {
        return $this->failedEvaluation()->isEmpty() && $this->passedEvaluation()->isNotEmpty();
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

    public function writeToCsv(string $filePath, ?string $submitter = null): void
    {
        if (! $this->allSuccessful()) {
            return;
        }

        $evaluations = $this->passedEvaluation();

        $csvFileExists = file_exists($filePath);
        $csvHeaderColumns = ['submitter', 'slackUsername', 'url', 'response', 'passed'];

        if ($csvFileExists) {
            $existingUrls = $this->getUniqueUrlHashMap($filePath, $evaluations, $csvHeaderColumns);
            $evaluations = $evaluations->filter(fn (array $item) => ! ($existingUrls[$item['url']] ?? false));
        }

        if ($evaluations->isEmpty()) {
            return;
        }

        $writer = Writer::createFromPath($filePath, 'a+');

        if (! $csvFileExists) {
            $writer->insertOne($csvHeaderColumns);
        }

        $lines = array_map(
            static fn (array $line) => array_merge([$submitter ?? $line[0]], $line),
            $this->getCsvLines($evaluations)
        );

        $writer->insertAll($lines);
    }

    public function normalizeUrl(string $url): string
    {
        return rtrim(explode('?', $url)[0], '/').'/';
    }

    private function getUniqueUrlHashMap(string $filePath, LazyCollection $evaluations, array $header): array
    {
        $urls = [];
        $insertUrls = $evaluations->map(fn (array $item) => $this->normalizeUrl($item['url']))->toArray();

        $reader = Reader::createFromStream(fopen($filePath, 'r+'))->setHeaderOffset(0);
        $records = $reader->getRecords($header);

        foreach ($records as $record) {
            $url = $this->normalizeUrl($record['url']);
            $urls[$url] = in_array($url, $insertUrls);
        }

        return array_filter($urls, static fn (bool $item) => $item);
    }

    private function getCsvLines(LazyCollection $collection): array
    {
        $enteredUrls = [];
        $lines = [];

        $collection->each(static function (array $item) use (&$lines, &$enteredUrls) {
            $url = $item['url'];
            $content = json_encode($item['content']);
            $passed = $item['passed'] ? 'true' : 'false';
            $username = $item['content']['slackUsername'] ?? '';

            if (! array_key_exists($url, $enteredUrls)) {
                $enteredUrls[$url] = true;
                $lines[] = [$username, $url, $content, $passed];
            }
        });

        return $lines;
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
            $evaluator->data($response, $url),
            $evaluator->rules($url),
            $evaluator->messages()
        );

        $content = [
            'request' => $evaluator->getEvaluationData($url),
            'response' => $evaluator->getContent($response, $url),
        ];

        return $this->buildPayload($url, $errors, $passed, $content);
    }

    private function buildPayload(string $url, array $errors = [], bool $passed = false, array $content = []): array
    {
        return ['url' => $url, 'content' => $content, 'passed' => $passed, 'errors' => $errors];
    }
}
