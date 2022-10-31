<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final class SlackService
{
    public function __construct(protected readonly Client $client)
    {
    }

    public function sendEvaluationResult(string $hook, bool $passed, array $errors = []): void
    {
        $this->client->post($hook, [
            RequestOptions::JSON => $passed ? $this->successMessage() : $this->failureMessage($errors),
        ]);
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
                        'text' => '🎉🥳 Your task was validated and submitted! Congrats! You do NOT need to do anything more but wait for promotion to the next stage.',
                    ],
                ],
                [
                    'type' => 'divider',
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
