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
            'slack_name' => ['required', 'string'],
            'utc_time' => ['required', 'date_format:Y-m-d H:i:s'],
            'track' => ['required', 'string', 'in:backend'],
            'github_file_url' => ['required', 'url'],
            'github_repo_url' => ['required', 'url'],
        
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
}
