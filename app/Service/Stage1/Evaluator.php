<?php

declare(strict_types=1);

namespace App\Service\Stage1;

use App\Service\Concerns\HandlesAndProvidesData;
use App\Service\Contracts\Evaluator as EvaluatorContract;

final class Evaluator implements EvaluatorContract
{
    use HandlesAndProvidesData;

    public function rules(string $url): array
    {
        return [
            // API response field validation
            'bio' => ['required', 'string'],
            'age' => ['required', 'integer'],
            'backend' => ['required', 'boolean'],
            'slackUsername' => ['required', 'string'],

            // Server response header validation
            'status_code' => ['required', 'integer', 'in:200'],
            'content_type' => ['required', 'string', 'regex:/^application\/json/'],
        ];
    }

    public function messages(): array
    {
        return [
            'in' => 'The :attribute must be one of the following types: :values',
        ];
    }

    public function csvFilePath(): string
    {
        return PROJECT_ROOT_PATH.'/storage/passed.csv';
    }

    public function csvHeaderColumns(): array
    {
        return ['slackUsername', 'url', 'response', 'passed'];
    }

    public function csvLine(array $item): array
    {
        $url = $item['url'];
        $content = json_encode($item['content']);
        $passed = $item['passed'] ? 'true' : 'false';
        $username = $item['content']['response']['slackUsername'];

        return [$username, $url, $content, $passed];
    }
}
