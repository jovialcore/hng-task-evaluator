<?php

declare(strict_types=1);

namespace App\Service\Stage2;

use Illuminate\Support\Arr;
use GuzzleHttp\Promise\Utils;
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

    public function fetch(array $urls): array
    {
        $data = Arr::except($this->data, 'result');
        $dataBonus = Arr::except($this->dataBonus, 'result');

        return Utils::settle(
            collect($urls)->mapWithKeys(function (string $url) use ($data, $dataBonus) {
                $bonusUrl = $url.'?bonus=true';
                $this->evaluationData[$url] = $this->data;
                $this->evaluationData[$bonusUrl] = $this->dataBonus;

                return [
                    $url => $this->http()->postAsync($url, [RequestOptions::FORM_PARAMS => $data]),
                    $bonusUrl => $this->http()->postAsync($bonusUrl, [RequestOptions::FORM_PARAMS => $dataBonus]),
                ];
            })->toArray()
        )->wait();
    }

    public function messages(): array
    {
        return [];
    }

    public function csvFilePath(): string
    {
        return PROJECT_ROOT_PATH.'/storage/passed2.csv';
    }
}
