<?php

declare(strict_types=1);

namespace App\Service\Stage2;

use Illuminate\Support\Arr;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use App\Rule\ExpectedEvaluationResult;
use App\Service\Concerns\HandlesAndProvidesData;
use App\Service\Contracts\Evaluator as EvaluatorContract;

final class Evaluator implements EvaluatorContract
{
    use HandlesAndProvidesData {
        data as protected traitData;
        getContent as protected traitGetContent;
    }

    public function __construct(protected readonly array $data, protected readonly array $dataBonus)
    {
    }

    public function rules(string $url): array
    {
        ['result' => $result, 'operation_type' => $operationType] = $this->getEvaluationData($url);

        return [
            // API response field validation
            'result' => ['bail', 'required', 'integer', ExpectedEvaluationResult::is($result)],
            'operation_type' => [
                'bail',
                'required',
                'string',
                ExpectedEvaluationResult::is($operationType, ExpectedEvaluationResult::OPERATION_TYPE),
            ],

            // Server response header validation
            'status_code' => ['required', 'integer', 'in:200'],
            'content_type' => ['required', 'string', 'regex:/^application\/json/'],
        ];
    }

    public function fetch(array $urls, bool $debug = false): array
    {
        $data = Arr::except($this->data, 'result');
        $dataBonus = Arr::except($this->dataBonus, 'result');

        if ($debug) {
            return $this->fetchDebug();
        }

        return Utils::settle(
            collect($urls)->mapWithKeys(function (string $url) use ($data, $dataBonus) {
                $bonusUrl = $url.'?bonus=true';
                $this->evaluationData[$url] = $this->data;
                $this->evaluationData[$bonusUrl] = $this->dataBonus;

                return [
                    $url => $this->http()->postAsync($url, [RequestOptions::JSON => $data]),
                    $bonusUrl => $this->http()->postAsync($bonusUrl, [RequestOptions::JSON => $dataBonus]),
                ];
            })->toArray()
        )->wait();
    }

    public function fetchDebug(): array
    {
        $this->evaluationData['https://api.example.com/1'] = $this->data;
        $this->evaluationData['https://api.example.com/1?bonus=true'] = $this->dataBonus;

        // mock response
        return [
            'https://api.example.com/1' => [
                'value' => new Response(200, ['Content-Type' => 'application/json'], json_encode([
                    'result' => 3,
                    'operation_type' => 'addition',
                ])),
                'state' => 'fulfilled',
            ],
            'https://api.example.com/1?bonus=true' => [
                'value' => new Response(200, ['Content-Type' => 'application/json'], json_encode([
                    'result' => 25,
                    'operation_type' => 'multiplication',
                ])),
                'state' => 'fulfilled',
            ],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function csvFilePath(): string
    {
        return PROJECT_ROOT_PATH.'/storage/passed2.csv';
    }

    public function csvHeaderColumns(): array
    {
        return ['slackUsername', 'url', 'response', 'passed', 'passedBonus'];
    }

    public function csvLine(array $item): array
    {
        $url = $item['url'];
        $passed = $item['passed'] ? 'true' : 'false';
        $username = $item['content']['response']['slackUsername'];
        $content = json_encode(
            ['normal' => [...$item['content']], 'bonus' => [
                'request' => $this->getEvaluationData($url.'?bonus=true'),
                'response' => $this->getContentForUrl($item['url'].'?bonus=true'),
            ]]
        );
        $bonusPassed = $item['bonusPassed'] ? 'true' : 'false';

        return [$username, $url, $content, $passed, $bonusPassed];
    }
}
