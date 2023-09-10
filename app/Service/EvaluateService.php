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

    public function failedErrors(bool $filterIgnorableErrors = false): array
    {
        return $this->failed->when(
            $filterIgnorableErrors,
            static fn (LazyCollection $items) => $items->filter(fn (array $item) => $item['canIgnore'] === false)
        )->map(fn (array $item) => $item['errors'])->flatten()->all();
    }

    public function allSuccessful(bool $filterIgnorableErrors = false): bool
    {
        return empty($this->failedErrors($filterIgnorableErrors)) && $this->passedEvaluation()->isNotEmpty();
    }

    public function setEvaluator(Evaluator $evaluator): void
    {
        $this->evaluator = $evaluator;
    }
    public function evaluate(array $urls, Evaluator $evaluator): void
    {
        $this->evaluator ??= $evaluator;


        $results = $evaluator->readUpdate($urls);

        $collection = LazyCollection::make(function () use ($results, $evaluator) {
            foreach ($results as $url => $result) {

                yield $this->parseAsyncResponses($result, $url, $evaluator);
            }
        });

        $this->passed = $collection->filter(fn ($item) => $item['passed']);
        $this->failed = $collection->filter(fn ($item) => !$item['passed']);
    }

    /**
     * @throws \League\Csv\CannotInsertRecord
     */
    public function writeToCsv(array $additional = [], array $options = []): ?bool
    {
        if (!$this->allSuccessful($options['filterIgnorableErrors'] ?? false)) {
            return false;
        }

        $evaluations = $this->passedEvaluation()->unique(fn (array $item) => $this->normalizeUrl($item['url']));

        $additionalLineItem = (array) $additional['line'] ?? [];
        $additionalHeaders = (array) $additional['headers'] ?? [];

        $csvFileExists = file_exists($this->evaluator->csvFilePath());
        $csvHeaderColumns = array_merge($additionalHeaders, $this->evaluator->csvHeaderColumns());

        if ($csvFileExists) {
            $existingUrls = $this->getUniqueUrlHashMap($evaluations->map(fn ($item) => $item['url'])->all(), $csvHeaderColumns);
            $evaluations = $evaluations->filter(
                fn (array $item) => ($existingUrls[$this->normalizeUrl($item['url'])] ?? false) === false
            );
        }

        if ($evaluations->isEmpty()) {
            return null;
        }

        $writer = Writer::createFromPath($this->evaluator->csvFilePath(), 'a+');

        if (!$csvFileExists) {
            $writer->insertOne($csvHeaderColumns);
        }

        $writer->insertAll(
            array_map(fn (array $line) => array_merge($additionalLineItem, $line), $this->getCsvLines($this->passed, $this->failed))
        );

        return true;
    }

    public function hasAlreadyEvaluatedUrls(array $insertUrls, array $additionalHeaders): bool
    {
        $csvFileExists = file_exists($this->evaluator->csvFilePath());

        if (!$csvFileExists) {
            return false;
        }

        $csvHeaderColumns = array_merge($additionalHeaders, $this->evaluator->csvHeaderColumns());
        $existingUrls = $this->getUniqueUrlHashMap($insertUrls, $csvHeaderColumns);
        $evaluations = array_filter(
            $insertUrls,
            fn (string $url) => ($existingUrls[$this->normalizeUrl($url)] ?? false) === false
        );

        return empty($evaluations);
    }

    public function normalizeUrl(string $url): string
    {
        return rtrim(explode('?', $url)[0], '/') . '/';
    }

    private function getUniqueUrlHashMap(array $insertUrls, array $header): array
    {
        $urls = [];
        $insertUrls = array_map(fn (string $url) => $this->normalizeUrl($url), $insertUrls);

        $reader = Reader::createFromStream(fopen($this->evaluator->csvFilePath(), 'r+'))->setHeaderOffset(0);
        $records = $reader->getRecords($header);

        foreach ($records as $record) {
            $url = $this->normalizeUrl($record['url']);
            $urls[$url] = in_array($url, $insertUrls);
        }

        return array_filter($urls, static fn (bool $item) => $item);
    }

    private function getCsvLines(LazyCollection $passed, LazyCollection $failed): array
    {
        $lines = [];
        $enteredUrls = [];

        $bonuses = $failed->collect()
            ->merge($passed)
            ->mapWithKeys(fn (array $item) => [$item['url'] => $item['passed']])
            ->filter(fn ($passed, $url) => str_contains($url, '?bonus=true'))
            ->mapWithKeys(fn ($passed, $url) => [$this->normalizeUrl($url) => $passed])
            ->all();

        $passed = $passed->collect()
            ->unique(fn (array $item) => $this->normalizeUrl($item['url']))
            ->map(fn (array $item) => array_merge($item, [
                'bonusPassed' => $bonuses[$this->normalizeUrl($item['url'])] ?? false,
            ]));

        $passed->each(function (array $item) use (&$lines, &$enteredUrls) {
            $url = $item['url'];

            if (!array_key_exists($url, $enteredUrls)) {
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
        // dd($response->getBody()->getContents());


        if ($result['state'] !== 'fulfilled' || $response === null) {
            $errors = [$result['reason']?->getMessage() ?? 'An unknown error occurred'];

            return $this->buildPayload($url, $errors, false, []);
        }


        if (isset($result['errors'])) {
            $errors = [$result['errors']];
            
            return $this->buildPayload($url, $errors, false, []);
        }

        
        ['passed' => $passed, 'errors' => $errors] = $this->validator->validate(
            $evaluator->data($response, $url),
            $evaluator->rules($url),
            $evaluator->messages()
        );


        $isBonus = str_contains($url, '?bonus=true');
        $content = [
            'request' => $evaluator->getEvaluationData($url),
            'response' => $evaluator->getContent($response, $url),
        ];

        return $this->buildPayload($url, $errors, $passed, $content, canIgnore: $isBonus);
    }

    private function buildPayload(string $url, array $errors, bool $passed, array $content, bool $canIgnore = false): array
    {
        return compact('url', 'errors', 'passed', 'content', 'canIgnore');
    }
}
