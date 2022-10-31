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
}
