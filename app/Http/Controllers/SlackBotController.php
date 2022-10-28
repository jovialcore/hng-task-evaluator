<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Validator;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use GuzzleHttp\RequestOptions;
use App\Service\EvaluateService;

final class SlackBotController extends Controller
{
    public function __invoke(Request $request, Client $client, Validator $validator, EvaluateService $evaluator)
    {
        $submittedUrl = preg_match('/https?:\/\/[^\s]+/', $request->get('text'), $matches) ? $matches[0] : null;

        $url = $validator->validate(['url' => $submittedUrl], ['url' => 'required|url'])['url'];

        $evaluator->evaluate([$url], $this->evaluator());

        $successful = $evaluator->passedEvaluation()->isNotEmpty() && $evaluator->failedEvaluation()->isEmpty();
        $errors = $evaluator->failedEvaluation()->map(fn (array $item) => $item['errors'])->toArray();

        $slackUsername = $request->get('user_name');

        $client->post($request->get('response_url'), [
            RequestOptions::JSON => $successful ? $this->successMessage() : $this->failureMessage($errors),
        ]);

        return '';
    }

    protected function successMessage(): array
    {
        return [
            'response_type' => 'ephemeral',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'emoji' => true,
                        'type' => 'plain_text',
                        'text' => '🎉🥳 Your task was validated and submitted! Congrats!',
                    ],
                ],
            ],
        ];
    }

    protected function failureMessage(array $errors): array
    {
        return [
            'response_type' => 'ephemeral',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'emoji' => true,
                        'type' => 'plain_text',
                        'text' => '❌ Your task verification failed. Sorry.',
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => implode("\n", array_map(static fn (string $error) => "- {$error}", $errors)),
                    ],
                ],
            ],
        ];
    }
}
