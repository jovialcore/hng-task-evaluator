<?php

declare(strict_types=1);

namespace App\Service;

use League\Csv\Reader;
use League\Csv\Writer;
use App\Service\Contracts\Evaluator;
use Illuminate\Support\LazyCollection;

final class EvaluateService
{
    protected Evaluator $evaluator;
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
        $this->evaluator = $evaluator;
        $results = $evaluator->fetch($urls, APP_DEBUG);

        $collection = LazyCollection::make(function () use ($results, $evaluator) {
            foreach ($results as $url => $result) {
                yield $this->parseAsyncResponses($result, $url, $evaluator);
            }
        });

        $this->passed = $collection->filter(fn ($item) => $item['passed']);
        $this->failed = $collection->filter(fn ($item) => ! $item['passed']);
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    public function writeToCsv(array $additional = []): void
    {
        if (! $this->allSuccessful()) {
            return;
        }

        $evaluations = $this->passedEvaluation();

        $additionalLineItem = (array) $additional['line'] ?? [];
        $additionalHeaders = (array) $additional['headers'] ?? [];

        $csvFileExists = file_exists($this->evaluator->csvFilePath());
        $csvHeaderColumns = array_merge($additionalHeaders, $this->evaluator->csvHeaderColumns());

        if ($csvFileExists) {
            $existingUrls = $this->getUniqueUrlHashMap($evaluations, $csvHeaderColumns);
            $evaluations = $evaluations->filter(
                fn (array $item) => ($existingUrls[$this->normalizeUrl($item['url'])] ?? false) === false
            );
        }

        if ($evaluations->isEmpty()) {
            return;
        }

        $writer = Writer::createFromPath($this->evaluator->csvFilePath(), 'a+');

        if (! $csvFileExists) {
            $writer->insertOne($csvHeaderColumns);
        }

        $writer->insertAll(
            array_map(fn (array $line) => array_merge($additionalLineItem, $line), $this->getCsvLines($evaluations))
        );
    }

    public function normalizeUrl(string $url): string
    {
        return rtrim(explode('?', $url)[0], '/').'/';
    }

    private function getUniqueUrlHashMap(LazyCollection $evaluations, array $header): array
    {
        $urls = [];
        $insertUrls = $evaluations->map(fn (array $item) => $this->normalizeUrl($item['url']))->toArray();

        $reader = Reader::createFromStream(fopen($this->evaluator->csvFilePath(), 'r+'))->setHeaderOffset(0);
        $records = $reader->getRecords($header);

        foreach ($records as $record) {
            $url = $this->normalizeUrl($record['url']);
            $urls[$url] = in_array($url, $insertUrls);
        }

        return array_filter($urls, static fn (bool $item) => $item);
    }

    private function getCsvLines(LazyCollection $collection): array
    {
        $lines = [];
        $enteredUrls = [];

        $collection->each(function (array $item) use (&$lines, &$enteredUrls) {
            $url = $item['url'];

            if (! array_key_exists($url, $enteredUrls)) {
                $enteredUrls[$url] = true;
                $lines[] = $this->evaluator->csvLine($item);
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
