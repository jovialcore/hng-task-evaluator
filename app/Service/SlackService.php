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
            RequestOptions::JSON => $passed
                ? $this->oneLiner('🎉🥳 Congrats! Your task was validated and submitted! You do *NOT* need to do anything more but wait for promotion to the next stage.')
                : $this->failureMessage($errors),
        ]);
    }

    public function sendStageHasEndedMessage(string $hook): void
    {
        $this->client->post($hook, [
            RequestOptions::JSON => $this->oneLiner("🥲 Submission is closed. Don't send another request to grade. *Don't make me bring out cane*. Thanks."),
        ]);
    }

    public function sendAlreadyEvaluatedMessage(string $hook, string $url): void
    {
        $this->client->post($hook, [
            RequestOptions::JSON => $this->oneLiner("🎯 [This URL]({$url}) has already been evaluated and graded. Please wait for a final review."),
        ]);
    }

    protected function oneLiner(string $message): array
    {
        return [
            'response_type' => 'ephemeral',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => ['emoji' => true, 'type' => 'mrkdwn', 'text' => $message],
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
