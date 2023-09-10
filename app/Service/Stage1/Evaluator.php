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
        
        $today = gmdate("l");
        $time = gmdate('Y-m-d\TH:i:s\Z');
       
        dd($today);
        return [
            // API response field validation
            'slack_name' => ['required', 'string', ],
            'utc_time' => ['required', "in:{$time}"],
            'track' => ['required', 'string', 'in:backend'],
            'github_file_url' => ['required', 'url'],
            'github_repo_url' => ['required', 'url'],
            'current_day' => ['required', 'string', "in:{$today}"],

            // Server response header validation
            'status_code' => ['required', 'integer', 'in:200'],
            'content_type' => ['required', 'string', 'regex:/^application\/json/'],
        ];
    }

    public function messages(): array
    {
        return [
            'in' => 'The :attribute should be in this format : :values',
        ];
    }


    public function csvFilePath(): string
    {
        return PROJECT_ROOT_PATH . '/storage/passed.csv';
    }
}
